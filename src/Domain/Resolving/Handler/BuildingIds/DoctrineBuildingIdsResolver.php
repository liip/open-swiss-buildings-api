<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\BuildingIds;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\ResolverResult;
use App\Domain\Resolving\Entity\ResolverTask;
use App\Domain\Resolving\Handler\AbstractDatabaseResolver;
use App\Domain\Resolving\Handler\TasksResultsConditions;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;

final readonly class DoctrineBuildingIdsResolver extends AbstractDatabaseResolver
{
    public function canResolveTasks(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::BUILDING_IDS === $type;
    }

    public function resolveTasks(ResolverJobIdentifier $job): void
    {
        $this->resolveTasksWithFiltering($job);
    }

    protected function buildResultInsertSQL(TasksResultsConditions $conditions): string
    {
        $resultTable = $this->entityManager->getClassMetadata(ResolverResult::class)->getTableName();
        $taskTable = $this->entityManager->getClassMetadata(ResolverTask::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        $conditions->addBuildingConditions('t.matching_building_id = b.building_id');

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        return "INSERT INTO {$resultTable} (id, job_id, confidence, match_type, country_code, building_id, building_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), t.job_id, t.confidence, t.match_type, b.country_code, t.matching_building_id, b.id, t.additional_data FROM {$taskTable} t " .
            "LEFT JOIN {$buildingEntranceTable} b ON {$conditions->getSQLBuildingConditions()}" .
            " WHERE t.job_id = {$conditions->jobIdParam}";
    }
}
