<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Entity\ResolverResult;
use App\Domain\Resolving\Entity\ResolverTask;
use App\Domain\Resolving\Exception\ResolverJobFailedException;
use App\Domain\Resolving\Handler\AbstractDatabaseResolver;
use App\Domain\Resolving\Handler\TasksResultsConditions;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAddressSearchResolver extends AbstractDatabaseResolver
{
    public function __construct(
        private DoctrineStreetIdMatcher $streetIdMatcher,
        private DoctrineStreetMatcher $streetMatcher,
        private DoctrineNothingMatcher $nothingMatcher,
        private DoctrineExactAddressMatcher $exactMatcher,
        private DoctrineClosestHouseNumberMatcher $closestStreetNumberMatcher,
        private SearchStreetMatcher $searchStreetMatcher,
        private DoctrineStreetWithRangeMatcher $streetWithRangeMatcher,
        private SearchMatcher $searchMatcher,
        ResolverJobReadRepositoryInterface $jobRepository,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($jobRepository, $entityManager);
    }

    public function canResolveTasks(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::ADDRESS_SEARCH === $type;
    }

    public function resolveTasks(ResolverJobIdentifier $job): void
    {
        try {
            $this->exactMatcher->matchExactly($job, 100);
            $this->exactMatcher->matchExactlyNormalized($job, 99);

            // Matching on Street
            $this->streetMatcher->matchStreetExact($job, 100);
            $this->streetMatcher->matchStreetNormalized($job, 99);

            // Matching with street-number range
            $this->streetWithRangeMatcher->matchStreetExactNumberRange($job, 98);
            $this->streetWithRangeMatcher->matchStreetExactNumberSuffixRange($job, 98);
            $this->streetWithRangeMatcher->matchStreetNormalizedNumberRange($job, 97);
            $this->streetWithRangeMatcher->matchStreetNormalizedNumberSuffixRange($job, 97);

            // Match by street search
            $this->searchStreetMatcher->matchStreetsBySearch($job, 95, 85);

            // Matching on Street-ID
            $this->streetIdMatcher->matchOnFullStreet($job, 1);
            $this->streetIdMatcher->matchWithoutSuffix($job, 2);
            $this->streetIdMatcher->matchWitSuffix($job, 3);
            $this->streetIdMatcher->matchWithOtherSuffix($job, 4);

            $this->closestStreetNumberMatcher->matchClosestHouseNumber($job);

            $this->searchMatcher->matchBySearch($job, 97, 70);
            $this->nothingMatcher->matchNothing($job);

            // Collecting all possible tasks
            $this->buildTasks($job);
            $this->resolveTasksWithFiltering($job);
        } catch (\Throwable $e) {
            throw ResolverJobFailedException::wrap($e);
        }
    }

    /**
     * Given the entries in the ResolverAddressMatch and ResolverAddress table, combine them into a set of possible
     * results and entries in the ResolverTask table.
     * combining the entries includes:
     * 1. use the higher confidence as the final results confidence
     * 2. merge the additional_data from multiple entries, so to keep all possible matches.
     *
     * The final result set will be computed by the entries in the ResolverTask table ( @see resolveIntoResults() )
     */
    private function buildTasks(ResolverJobIdentifier $job): void
    {
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $taskTable = $this->entityManager->getClassMetadata(ResolverTask::class)->getTableName();

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$taskTable} (id, job_id, confidence, match_type, matching_unique_hash, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.job_id, MAX(m.confidence), string_agg(DISTINCT m.match_type, :matchTypeSeparator), CONCAT(m.matching_building_id, m.matching_entrance_id), m.matching_building_id, m.matching_entrance_id, json_agg(a_data ORDER BY m.address_id) FROM {$matchTable} m " .
            "INNER JOIN {$addressTable} a ON m.address_id = a.id " .
            'CROSS JOIN json_array_elements(m.additional_data) AS a_data ' .
            'WHERE a.job_id = :jobId AND m.matching_building_id IS NOT NULL AND m.matching_entrance_id IS NOT NULL ' .
            'GROUP BY a.job_id, m.matching_building_id, m.matching_entrance_id';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'jobId' => $job->id,
            'matchTypeSeparator' => AdditionalData::ADDITIONAL_DATA_SEPARATOR,
        ]);

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$taskTable} (id, job_id, confidence, match_type, matching_unique_hash, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.job_id, m.confidence, m.match_type, m.address_id, NULL, NULL, m.additional_data FROM {$matchTable} m " .
            "INNER JOIN {$addressTable} a ON m.address_id = a.id " .
            'WHERE a.job_id = :jobId AND (m.matching_building_id IS NULL OR m.matching_entrance_id IS NULL)';

        $this->entityManager->getConnection()->executeStatement($sql, ['jobId' => $job->id]);
    }

    protected function buildResultInsertSQL(TasksResultsConditions $conditions): string
    {
        $resultTable = $this->entityManager->getClassMetadata(ResolverResult::class)->getTableName();
        $taskTable = $this->entityManager->getClassMetadata(ResolverTask::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        $conditions->addBuildingConditions('t.matching_building_id = b.building_id');
        $conditions->addBuildingConditions('t.matching_entrance_id = b.entrance_id');

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        return "INSERT INTO {$resultTable} (id, job_id, confidence, match_type, country_code, building_id, entrance_id, building_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), t.job_id, t.confidence, t.match_type, b.country_code, t.matching_building_id, t.matching_entrance_id, b.id, t.additional_data FROM {$taskTable} t " .
            "LEFT JOIN {$buildingEntranceTable} b ON {$conditions->getSQLBuildingConditions()}" .
            " WHERE t.job_id = {$conditions->jobIdParam}" .
            ' ON CONFLICT (job_id, country_code, building_entrance_id) DO NOTHING';
    }
}
