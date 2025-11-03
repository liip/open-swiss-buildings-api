<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Contract\Result\ResolverResultReadRepositoryInterface;
use App\Domain\Resolving\Contract\Result\ResolverResultWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverResult as ResolverResultEntity;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Confidence;
use App\Domain\Resolving\Model\Result\Address;
use App\Domain\Resolving\Model\Result\ResolverResult;
use App\Infrastructure\Doctrine\PostgreSQLCursorFetcher;
use App\Infrastructure\Pagination;
use App\Infrastructure\PostGis\CoordinatesParser;
use App\Infrastructure\Serialization\Decoder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverResultEntity>
 *
 * @phpstan-import-type AdditionalDataAsArray from AdditionalData
 */
final class DoctrineResolverResultRepository extends ServiceEntityRepository implements
    ResolverResultReadRepositoryInterface,
    ResolverResultWriteRepositoryInterface
{
    private const array COLUMNS_RESULT = [
        'r.confidence',
        'r.matchType',
        'r.buildingEntranceId',
        'r.countryCode',
        'r.buildingId',
        'e.entranceId',
        'e.municipalityCode',
        'e.postalCode',
        'e.locality',
        'e.streetName',
        'e.streetHouseNumber',
        'e.streetHouseNumberSuffix',
        'e.geoCoordinatesWGS84',
        'r.additionalData',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResolverResultEntity::class);
    }

    public function getResults(Uuid $jobId): iterable
    {
        $metadataResult = $this->getClassMetadata();
        $tableNameResult = $metadataResult->getTableName();
        $metadataBuildingEntrance = $this->getEntityManager()->getClassMetadata(BuildingEntrance::class);
        $metadataBuildingEntrance->getTableName();

        $columns = [];
        foreach (self::COLUMNS_RESULT as $fullFieldName) {
            [$tableAlias, $fieldName] = explode('.', $fullFieldName);
            switch ($tableAlias) {
                case 'r':
                    $columnName = $metadataResult->getColumnName($fieldName);
                    break;
                case 'e':
                    $columnName = $metadataBuildingEntrance->getColumnName($fieldName);
                    break;
                default:
                    throw new \LogicException("Unexpected table alias {$tableAlias}");
            }
            $fullColumnName = "{$tableAlias}.{$columnName}";
            if ('geoCoordinatesWGS84' === $fieldName) {
                $fullColumnName = "ST_AsEWKT({$fullColumnName})";
            }
            $columns[] = "{$fullColumnName} AS {$fieldName}";
        }

        $sql = 'SELECT ' . implode(', ', $columns) . " FROM {$tableNameResult} r LEFT JOIN building_entrance e ON r.building_entrance_id = e.id WHERE r.job_id = :jobId ORDER BY r.building_id, r.entrance_id";
        $parameters = ['jobId' => $jobId];

        foreach (PostgreSQLCursorFetcher::fetch($this->getEntityManager()->getConnection(), $sql, $parameters) as $row) {
            yield $this->createModel($row);
        }
    }

    public function getPaginatedResults(Uuid $jobId, Pagination $pagination): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ' . implode(', ', self::COLUMNS_RESULT) . ' FROM ' . ResolverResultEntity::class . ' r ' .
                'LEFT JOIN ' . BuildingEntrance::class . ' e WITH r.buildingEntranceId = e.id ' .
                'WHERE r.jobId = :jobId ' .
                'ORDER BY r.buildingId, r.entranceId',
            )
            ->setParameter('jobId', $jobId)
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset)
        ;

        foreach ($query->toIterable() as $row) {
            yield $this->createModel($row);
        }
    }

    public function deleteByJobId(Uuid $jobId): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverResultEntity::class . ' t' .
                ' WHERE t.job = :jobId',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->execute();
    }

    /**
     * @param array<string|int, mixed> $row
     */
    private function createModel(array $row): ResolverResult
    {
        $address = null;
        $municipalityCode = Decoder::readOptionalString($row, 'municipalityCode');
        $postalCode = Decoder::readOptionalString($row, 'postalCode');
        $locality = Decoder::readOptionalString($row, 'locality');
        $streetName = Decoder::readOptionalNonEmptyString($row, 'streetName', true);
        $countryCode = Decoder::readOptionalNonEmptyString($row, 'countryCode', true);
        if (!\in_array(null, [$municipalityCode, $postalCode, $locality, $streetName, $countryCode], true)
        ) {
            $streetHouseNumber = Decoder::readOptionalPositiveInt($row, 'streetHouseNumber', true);
            $numberSuffix = Decoder::readOptionalNonEmptyString($row, 'streetHouseNumberSuffix', true);
            $numberSuffix ??= '';
            $address = new Address(
                $municipalityCode,
                $postalCode,
                $locality,
                $streetName,
                $streetHouseNumber . $numberSuffix,
                $countryCode,
            );
        }

        $coordinates = null;
        if (null !== ($geoCoordinates = Decoder::readOptionalNonEmptyString($row, 'geoCoordinatesWGS84'))) {
            $coordinates = CoordinatesParser::parseWGS84($geoCoordinates);
        }

        return new ResolverResult(
            confidence: Confidence::fromInt(Decoder::readInt($row, 'confidence')),
            matchType: Decoder::readString($row, 'matchType'),
            buildingEntranceId: Decoder::readOptionalUuidAsString($row, 'buildingEntranceId'),
            countryCode: Decoder::readOptionalNonEmptyString($row, 'countryCode'),
            buildingId: Decoder::readOptionalString($row, 'buildingId', true),
            entranceId: Decoder::readOptionalNonEmptyString($row, 'entranceId'),
            address: $address,
            coordinates: $coordinates,
            additionalData: AdditionalData::createFromList(Decoder::readAdditionalDataList($row, 'additionalData')),
        );
    }
}
