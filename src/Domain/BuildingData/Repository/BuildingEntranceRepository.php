<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Repository;

use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use App\Domain\BuildingData\Entity\BuildingEntrance as BuildingEntranceEntity;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenPruned;
use App\Domain\BuildingData\Model\BuildingEntrance;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\BuildingData\Model\BuildingEntranceFilter;
use App\Domain\BuildingData\Model\BuildingEntranceStatistics;
use App\Infrastructure\Address\AddressNormalizer;
use App\Infrastructure\Doctrine\BatchInsertStatementBuilder;
use App\Infrastructure\Doctrine\PostgreSQLCursorFetcher;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use App\Infrastructure\PostGis\CoordinatesParser;
use App\Infrastructure\PostGis\SRIDEnum;
use App\Infrastructure\Serialization\Encoder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<BuildingEntranceEntity>
 */
final class BuildingEntranceRepository extends ServiceEntityRepository implements
    BuildingEntranceReadRepositoryInterface,
    BuildingEntranceWriteRepositoryInterface
{
    private const int INSERT_BATCH_SIZE = 1000;

    public function __construct(
        ManagerRegistry $registry,
        private readonly AddressNormalizer $normalizer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($registry, BuildingEntranceEntity::class);
    }

    public function deleteAll(): void
    {
        $this->getEntityManager()
            ->createQuery('DELETE FROM ' . BuildingEntranceEntity::class)
            ->execute()
        ;
    }

    public function store(iterable $buildingEntrances): iterable
    {
        $columnDefinitions = [
            'id' => ':id%i%',
            'country_code' => ':country_code%i%',
            'building_id' => ':building_id%i%',
            'entrance_id' => ':entrance_id%i%',
            'address_id' => ':address_id%i%',
            'street_id' => ':street_id%i%',
            'street_name' => ':street_name%i%',
            'street_name_normalized' => ':street_name_normalized%i%',
            'street_name_abbreviated' => ':street_name_abbreviated%i%',
            'street_name_abbreviated_normalized' => ':street_name_abbreviated_normalized%i%',
            'street_house_number' => ':street_house_number%i%',
            'street_house_number_suffix' => ':street_house_number_suffix%i%',
            'street_house_number_suffix_normalized' => ':street_house_number_suffix_normalized%i%',
            'street_name_language' => ':street_name_language%i%',
            'postal_code' => ':postal_code%i%',
            'locality' => ':locality%i%',
            'locality_normalized' => ':locality_normalized%i%',
            'municipality_code' => ':municipality_code%i%',
            'municipality' => ':municipality%i%',
            'canton_code' => ':canton%i%',
            'coordinates_lv95' => ':coordinates_lv95%i%',
            'geo_coordinates_lv95' => 'ST_GeomFromEWKT(:geo_coordinates_lv95%i%)',
            'geo_coordinates_wgs84' => 'ST_Transform(ST_GeomFromEWKT(:geo_coordinates_lv95%i%), ' . SRIDEnum::WGS84->value . ')',
            'imported_at' => ':imported_at%i%',
        ];
        $conflictUpdates = array_values(array_filter(
            array_keys($columnDefinitions),
            static fn($column): bool => !\in_array($column, ['id', 'country_code', 'building_id', 'entrance_id', 'street_name_language'], true),
        ));

        $batchSql = BatchInsertStatementBuilder::generate(
            $this->getClassMetadata()->getTableName(),
            self::INSERT_BATCH_SIZE,
            $columnDefinitions,
            ['country_code', 'building_id', 'entrance_id', 'street_name_language'],
            $conflictUpdates,
        );

        $batchStmt = $this->getEntityManager()->getConnection()->prepare($batchSql);

        $bindValues = function (Statement $stmt, int $i, BuildingEntranceData $buildingEntrance): void {
            ['coords' => $coordinatesLV95, 'geoCoords' => $geoCoordinatesLV95] = CoordinatesParser::extractCoordsFromLV95ByParts(
                $buildingEntrance->geoCoordinateEastLV95,
                $buildingEntrance->geoCoordinateNorthLV95,
            );

            $streetName = $streetNameAbbreviated = null;
            if (null !== $buildingEntrance->street && null !== $buildingEntrance->street->streetName) {
                $streetName = $buildingEntrance->street->streetName;
            }
            if (null !== $buildingEntrance->streetAbbreviated && null !== $buildingEntrance->streetAbbreviated->streetName) {
                $streetNameAbbreviated = $buildingEntrance->streetAbbreviated->streetName;
            }

            $stmt->bindValue('id' . $i, Uuid::v7());
            $stmt->bindValue('building_id' . $i, $buildingEntrance->buildingId);
            $stmt->bindValue('entrance_id' . $i, $buildingEntrance->entranceId);
            $stmt->bindValue('address_id' . $i, $buildingEntrance->addressId);
            $stmt->bindValue('country_code' . $i, $buildingEntrance->countryCode->value);
            $stmt->bindValue('street_id' . $i, $buildingEntrance->streetId);
            $stmt->bindValue('street_name' . $i, $streetName ?? '');
            $stmt->bindValue('street_name_normalized' . $i, null !== $streetName ? $this->normalizer->normalize($streetName) : '');
            $stmt->bindValue('street_name_abbreviated' . $i, $streetNameAbbreviated ?? '');
            $stmt->bindValue('street_name_abbreviated_normalized' . $i, null !== $streetNameAbbreviated ? $this->normalizer->normalize($streetNameAbbreviated) : '');
            $stmt->bindValue('street_house_number' . $i, $buildingEntrance->street->number->number ?? 0);
            $stmt->bindValue('street_house_number_suffix' . $i, $buildingEntrance->street->number->suffix ?? '');
            $stmt->bindValue('street_house_number_suffix_normalized' . $i, $this->normalizer->normalize($buildingEntrance->street->number->suffix ?? ''));
            $stmt->bindValue('street_name_language' . $i, $buildingEntrance->streetNameLanguage->value);
            $stmt->bindValue('postal_code' . $i, $buildingEntrance->postalCode);
            $stmt->bindValue('locality' . $i, $buildingEntrance->locality);
            $stmt->bindValue('locality_normalized' . $i, $this->normalizer->normalizeLocality($buildingEntrance->locality, $buildingEntrance->municipality, $buildingEntrance->cantonCode));
            $stmt->bindValue('municipality_code' . $i, $buildingEntrance->municipalityCode);
            $stmt->bindValue('municipality' . $i, $buildingEntrance->municipality);
            $stmt->bindValue('canton' . $i, $buildingEntrance->cantonCode);
            $stmt->bindValue('coordinates_lv95' . $i, $coordinatesLV95, Types::JSON);
            $stmt->bindValue('geo_coordinates_lv95' . $i, $geoCoordinatesLV95);
            $stmt->bindValue('imported_at' . $i, $this->clock->now(), Types::DATETIME_IMMUTABLE);
        };

        $batchEntries = [];
        $i = 1;
        foreach ($buildingEntrances as $buildingEntrance) {
            $batchEntries[] = $buildingEntrance;

            $bindValues($batchStmt, $i, $buildingEntrance);

            if (self::INSERT_BATCH_SIZE === $i) {
                $batchStmt->executeStatement();
                $batchEntries = [];
                $i = 0;
            }
            yield $buildingEntrance;
            ++$i;
        }

        if ([] !== $batchEntries) {
            $batchSql = BatchInsertStatementBuilder::generate(
                $this->getClassMetadata()->getTableName(),
                \count($batchEntries),
                $columnDefinitions,
                ['building_id', 'entrance_id', 'country_code', 'street_name_language'],
                $conflictUpdates,
            );
            $batchStmt = $this->getEntityManager()->getConnection()->prepare($batchSql);

            $i = 1;
            foreach ($batchEntries as $buildingEntrance) {
                $bindValues($batchStmt, $i, $buildingEntrance);

                yield $buildingEntrance;
                ++$i;
            }

            $batchStmt->executeStatement();
        }
    }

    public function getStatistics(CountryCodeEnum $countryCode): BuildingEntranceStatistics
    {
        $qb = $this->createQueryBuilder('b')
            ->select('count(b.id) AS total')
            ->addSelect('b.cantonCode AS cantonCode')
            ->where('b.countryCode = :countryCode')
            ->groupBy('b.cantonCode')
            ->setParameter('countryCode', $countryCode->value)
        ;

        /** @phpstan-ignore doctrine.queryBuilderDynamicArgument */
        $result = $qb->getQuery()->getArrayResult();

        $total = 0;
        $byCanton = [];
        foreach ($result as $aggregatedResult) {
            $byCanton[$aggregatedResult['cantonCode']] = $aggregatedResult['total'];
            $total += abs((int) $aggregatedResult['total']);
        }

        return new BuildingEntranceStatistics(
            total: $total,
            byCanton: $byCanton,
        );
    }

    public function deleteOutdatedBuildingEntrances(int $activeDays, ?CountryCodeEnum $countryCode = null): int
    {
        $importDateBefore = $this->clock->now()->sub(new \DateInterval("P{$activeDays}D"));

        $qb = $this->createQueryBuilder('b')
            ->where('b.importedAt <=  :importedat')
            ->setParameter('importedAt', $importDateBefore, Types::DATETIME_IMMUTABLE)
        ;

        if ($countryCode instanceof CountryCodeEnum) {
            $qb->andWhere('b.countryCode = :countryCode')
                ->setParameter('countryCode', $countryCode)
            ;
        }

        /** @phpstan-ignore doctrine.queryBuilderDynamicArgument */
        $query = $qb->delete()->getQuery();
        $result = (int) $query->execute();

        $this->eventDispatcher->dispatch(new BuildingEntrancesHaveBeenPruned($importDateBefore, $countryCode));

        return $result;
    }

    public function countOutdatedBuildingEntrances(int $activeDays): int
    {
        $importDateBefore = $this->clock->now()->sub(new \DateInterval("P{$activeDays}D"));

        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(b.id) FROM ' . BuildingEntranceEntity::class . ' b ' .
                'WHERE b.importedAt <= :importedAt',
            )
            ->setParameter('importedAt', $importDateBefore, Types::DATETIME_IMMUTABLE)
        ;

        return $this->fetchCount($query);
    }

    public function getOutdatedBuildingEntrances(int $activeDays): iterable
    {
        $importDateBefore = $this->clock->now()->sub(new \DateInterval("P{$activeDays}D"));

        $tableName = $this->getClassMetadata()->getTableName();
        $sql = "SELECT b.*, ST_AsEWKT(b.geo_coordinates_wgs84) AS geo_coordinates_wgs84 FROM {$tableName} b WHERE b.imported_at <= :timestamp";
        $parameters = ['timestamp' => $importDateBefore->format(Encoder::DATE_FORMAT)];

        foreach (PostgreSQLCursorFetcher::fetch($this->getEntityManager()->getConnection(), $sql, $parameters) as $row) {
            yield BuildingEntrance::fromScalarArray($row);
        }
    }

    public function countBuildingEntrances(?CountryCodeEnum $countryCode = null): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('count(b.id) AS total')
        ;

        if ($countryCode instanceof CountryCodeEnum) {
            $qb->andWhere('b.countryCode = :countryCode')
                ->setParameter('countryCode', $countryCode)
            ;
        }

        /** @phpstan-ignore doctrine.queryBuilderDynamicArgument */
        $count = $qb->getQuery()->getSingleScalarResult();
        if (!is_numeric($count)) {
            throw new \UnexpectedValueException('Expected query result to be numeric, but got ' . get_debug_type($count));
        }

        return (int) $count;
    }

    public function getBuildingEntrances(?CountryCodeEnum $countryCode = null): iterable
    {
        $tableName = $this->getClassMetadata()->getTableName();
        $sql = "SELECT b.*, ST_AsEWKT(b.geo_coordinates_wgs84) AS geo_coordinates_wgs84 FROM {$tableName} b";
        $parameters = [];

        if ($countryCode instanceof CountryCodeEnum) {
            $sql .= ' WHERE b.country_code = :countryCode';
            $parameters = ['countryCode' => $countryCode->value];
        }

        foreach (PostgreSQLCursorFetcher::fetch($this->getEntityManager()->getConnection(), $sql, $parameters) as $row) {
            yield BuildingEntrance::fromScalarArray($row);
        }
    }

    public function findBuildingEntrance(Uuid $id): ?BuildingEntrance
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT b FROM ' . BuildingEntranceEntity::class . ' b WHERE b.id = :id',
            )
            ->setParameter('id', $id)
        ;

        $row = $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
        if (null === $row) {
            return null;
        }

        return BuildingEntrance::fromArray($row);
    }

    public function getBuildingEntrancesImportedSince(\DateTimeImmutable $timestamp): iterable
    {
        $tableName = $this->getClassMetadata()->getTableName();
        $sql = "SELECT b.*, ST_AsEWKT(b.geo_coordinates_wgs84) AS geo_coordinates_wgs84 FROM {$tableName} b WHERE b.imported_at >= :timestamp";
        $parameters = ['timestamp' => $timestamp->format(Encoder::DATE_FORMAT)];

        foreach (PostgreSQLCursorFetcher::fetch($this->getEntityManager()->getConnection(), $sql, $parameters) as $row) {
            yield BuildingEntrance::fromScalarArray($row);
        }
    }

    public function getPaginatedBuildingEntrances(Pagination $pagination, BuildingEntranceFilter $filter): iterable
    {
        $sql = 'SELECT b FROM ' . BuildingEntranceEntity::class . ' b';

        $whereClauses = [];
        $parameters = [];
        if (null !== ($cantonCodes = $filter->cantonCodes)) {
            $whereClauses[] = 'b.cantonCode IN (:cantonCodes)';
            $parameters['cantonCodes'] = array_map(static fn(string $code): string => strtoupper($code), $cantonCodes);
        }
        if (null !== ($buildingIds = $filter->buildingIds)) {
            $whereClauses[] = 'b.buildingId IN (:buildingIds)';
            $parameters['buildingIds'] = $buildingIds;
        }
        if (null !== ($entranceIds = $filter->entranceIds)) {
            $whereClauses[] = 'b.entranceId IN (:entranceIds)';
            $parameters['entranceIds'] = $entranceIds;
        }
        if (null !== ($countryCodes = $filter->countryCodes)) {
            $whereClauses[] = 'b.countryCode IN (:countryCodes)';
            $parameters['countryCodes'] = $countryCodes;
        }
        if (null !== ($municipalities = $filter->municipalities)) {
            $whereClauses[] = 'b.municipality IN (:municipalities)';
            $parameters['municipalities'] = $municipalities;
        }
        if (null !== ($streetNames = $filter->streetNames)) {
            $whereClauses[] = 'b.streetName IN (:streetNames)';
            $parameters['streetNames'] = $streetNames;
        }
        if (null !== ($streetIds = $filter->streetIds)) {
            $whereClauses[] = 'b.streetId IN (:streetIds)';
            $parameters['streetIds'] = $streetIds;
        }
        if (null !== ($languages = $filter->languages)) {
            $whereClauses[] = 'b.streetLanguage IN (:languages)';
            $parameters['languages'] = $languages;
        }

        if ([] !== $whereClauses) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters($parameters)
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset)
        ;

        foreach ($query->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $row) {
            yield BuildingEntrance::fromArray($row);
        }
    }

    /**
     * @param AbstractQuery<int, int> $query
     *
     * @return non-negative-int
     */
    private function fetchCount(AbstractQuery $query): int
    {
        $result = $query->getSingleScalarResult();
        if (!is_numeric($result)) {
            throw new \UnexpectedValueException('Expected query result to be numeric, but got ' . get_debug_type($result));
        }

        $count = (int) $result;
        if ($count < 0) {
            throw new \UnexpectedValueException("Expected count to not be negative, but got {$count}");
        }

        return $count;
    }
}
