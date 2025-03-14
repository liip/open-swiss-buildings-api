<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Repository;

use App\Domain\FederalData\Contract\FederalBuildingDataRepositoryInterface;
use App\Domain\FederalData\Entity\Building;
use App\Domain\FederalData\Entity\Entrance;
use App\Domain\FederalData\Model\BuildingEntranceData;
use App\Domain\FederalData\Model\BuildingStatusEnum;
use App\Domain\FederalData\Model\EntranceLanguageEnum;
use App\Domain\FederalData\Model\FederalEntranceFilter;
use App\Infrastructure\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entrance>
 */
final class EntranceRepository extends ServiceEntityRepository implements FederalBuildingDataRepositoryInterface
{
    protected const array EXCLUDED_BUILDING_STATUSES = [
        BuildingStatusEnum::DEMOLISHED->value,
        BuildingStatusEnum::NOT_BUILT->value,
        BuildingStatusEnum::AUTHORIZED->value,
        BuildingStatusEnum::PLANNED->value,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entrance::class);
    }

    public function countBuildingData(): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(b.EGID) FROM ' . Entrance::class . ' e ' .
                'INNER JOIN ' . Building::class . ' b WITH e.EGID = b.EGID ' .
                'WHERE b.GSTAT NOT IN (:excludedBuildingStatuses)',
            )
            ->setParameter('excludedBuildingStatuses', self::EXCLUDED_BUILDING_STATUSES, ArrayParameterType::STRING)
        ;

        $count = $query->getSingleScalarResult();
        if (!is_numeric($count)) {
            throw new \UnexpectedValueException('Expected query result to be numeric, but got ' . get_debug_type($count));
        }

        return (int) $count;
    }

    public function getBuildingData(): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                $this->getBaseSelectSql() . ' WHERE b.GSTAT NOT IN (:excludedBuildingStatuses)',
            )
            ->setParameter('excludedBuildingStatuses', self::EXCLUDED_BUILDING_STATUSES, ArrayParameterType::STRING)
        ;

        foreach ($query->toIterable() as $row) {
            yield $this->buildModel($row);
        }
    }

    public function getPaginatedBuildingData(Pagination $pagination, FederalEntranceFilter $filter): iterable
    {
        $sql = $this->getBaseSelectSql();

        $whereClauses = [];
        $parameters = [];
        if (null !== ($cantonCodes = $filter->cantonCodes)) {
            $whereClauses[] = 'b.GDEKT IN (:cantonCodes)';
            $parameters['cantonCodes'] = array_map(static fn(string $code): string => strtoupper($code), $cantonCodes);
        }
        if (null !== ($buildingIds = $filter->buildingIds)) {
            $whereClauses[] = 'e.EGID IN (:buildingIds)';
            $parameters['buildingIds'] = $buildingIds;
        }
        if (null !== ($entranceIds = $filter->entranceIds)) {
            $whereClauses[] = 'e.EDID IN (:entranceIds)';
            $parameters['entranceIds'] = $entranceIds;
        }
        if (null !== ($municipalities = $filter->municipalities)) {
            $whereClauses[] = 'b.GGDENAME IN (:municipalities)';
            $parameters['municipalities'] = $municipalities;
        }
        if (null !== ($streetNames = $filter->streetNames)) {
            $whereClauses[] = 'e.STRNAME IN (:streetNames)';
            $parameters['streetNames'] = $streetNames;
        }
        if (null !== ($streetIds = $filter->streetIds)) {
            $whereClauses[] = 'e.ESID IN (:streetIds)';
            $parameters['streetIds'] = $streetIds;
        }
        if (null !== ($languages = $filter->languages)) {
            $whereClauses[] = 'e.STRSP IN (:languages)';
            $parameters['languages'] = $languages;
        }
        if (null !== ($buildingStatuses = $filter->buildingStatuses)) {
            $whereClauses[] = 'b.GSTAT IN (:buildingStatuses)';
            $parameters['buildingStatuses'] = $buildingStatuses;
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

        foreach ($query->toIterable() as $row) {
            yield $this->buildModel($row);
        }
    }

    private function getBaseSelectSql(): string
    {
        $columns = [
            'b.EGID',
            'e.EDID',
            'e.EGAID',
            'b.GDEKT',
            'e.DEINR',
            'e.ESID',
            'e.STRNAME',
            'e.STRNAMK',
            'e.STRSP',
            'e.DPLZ4',
            'e.DPLZNAME',
            'b.GDEKT',
            'b.GGDENR',
            'b.GGDENAME',
            'e.DKODE',
            'e.DKODN',
            'b.GKODE',
            'b.GKODN',
            'b.GSTAT',
        ];

        return 'SELECT ' . implode(', ', $columns) .
            ' FROM ' . Entrance::class . ' e' .
            ' INNER JOIN ' . Building::class . ' b WITH e.EGID = b.EGID';
    }

    /**
     * @param array{
     *     EGID: string,
     *     EDID: string,
     *     EGAID: string,
     *     DEINR: string,
     *     ESID: string,
     *     STRNAME: string,
     *     STRNAMK: string,
     *     STRSP: EntranceLanguageEnum,
     *     DPLZ4: string,
     *     DPLZNAME: string,
     *     GGDENR: string,
     *     GGDENAME: string,
     *     GDEKT: string,
     *     DKODE: string,
     *     DKODN: string,
     *     GKODE: string,
     *     GKODN: string,
     *     GSTAT: BuildingStatusEnum,
     * } $row
     */
    private function buildModel(array $row): BuildingEntranceData
    {
        $coordLV95East = $row['DKODE'];
        $coordLV95North = $row['DKODN'];
        if ('' === $coordLV95East || '' === $coordLV95North) {
            // If the Entrance coordinates are empty, we fallback to the Building coorindates, if available
            $coordLV95East = $row['GKODE'];
            $coordLV95North = $row['GKODN'];
        }

        return new BuildingEntranceData(
            buildingId: $row['EGID'],
            entranceId: $row['EDID'],
            addressId: $row['EGAID'],
            streetId: $row['ESID'],
            streetName: $row['STRNAME'],
            streetNameAbbreviation: $row['STRNAMK'],
            streetNameLanguage: $row['STRSP'],
            streetHouseNumber: $row['DEINR'],
            postalCode: $row['DPLZ4'],
            locality: $row['DPLZNAME'],
            municipalityCode: $row['GGDENR'],
            municipality: $row['GGDENAME'],
            cantonCode: $row['GDEKT'],
            geoCoordinateEastLV95: $coordLV95East,
            geoCoordinateNorthLV95: $coordLV95North,
            buildingStatus: $row['GSTAT'],
        );
    }
}
