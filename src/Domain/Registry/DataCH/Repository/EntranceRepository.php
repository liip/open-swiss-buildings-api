<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Repository;

use App\Domain\Registry\DataCH\Entity\Building;
use App\Domain\Registry\DataCH\Entity\Entrance;
use App\Domain\Registry\DataCH\Model\SwissBuildingStatusEnum;
use App\Domain\Registry\DataCH\Model\SwissLanguageEnum;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\LanguageEnum;
use App\Infrastructure\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entrance>
 *
 * @phpstan-type BuildingData array{
 *      EGID: string,
 *      EDID: string,
 *      EGAID: string,
 *      DEINR: string,
 *      ESID: string,
 *      STRNAME: string,
 *      STRNAMK: string,
 *      STRSP: SwissLanguageEnum,
 *      DPLZ4: string,
 *      DPLZNAME: string,
 *      GGDENR: string,
 *      GGDENAME: string,
 *      GDEKT: string,
 *      DKODE: string,
 *      DKODN: string,
 *      GKODE: string,
 *      GKODN: string,
 *      GSTAT: SwissBuildingStatusEnum,
 *  }
 */
final class EntranceRepository extends ServiceEntityRepository
{
    private const array EXCLUDED_BUILDING_STATUSES = [
        SwissBuildingStatusEnum::DEMOLISHED->value,
        SwissBuildingStatusEnum::NOT_BUILT->value,
        SwissBuildingStatusEnum::AUTHORIZED->value,
        SwissBuildingStatusEnum::PLANNED->value,
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

    /**
     * @return iterable<BuildingData>
     */
    public function getBuildingData(): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                $this->getBaseSelectSql() . ' WHERE b.GSTAT NOT IN (:excludedBuildingStatuses)',
            )
            ->setParameter('excludedBuildingStatuses', self::EXCLUDED_BUILDING_STATUSES, ArrayParameterType::STRING)
        ;

        foreach ($query->toIterable() as $row) {
            yield $row;
        }
    }

    /**
     * @return iterable<BuildingData>
     */
    public function getPaginatedBuildingData(Pagination $pagination, BuildingEntranceFilter $filter): iterable
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
        if (null !== ($municipalities = $filter->municipalityNames)) {
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
            $swissLanguageCodes = array_map(static fn(LanguageEnum $l): SwissLanguageEnum => SwissLanguageEnum::fromLanguage($l), $languages);

            $whereClauses[] = 'e.STRSP IN (:languages)';
            $parameters['languages'] = $swissLanguageCodes;
        }
        if (null !== ($swissBuildingStatusCodes = $filter->buildingStatuses)) {
            $whereClauses[] = 'b.GSTAT IN (:buildingStatuses)';
            $parameters['buildingStatuses'] = $swissBuildingStatusCodes;
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
            yield $row;
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
}
