<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\MunicipalitiesCodes;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Contract\Job\TaskResolverInterface;
use App\Domain\Resolving\Entity\ResolverResult;
use App\Domain\Resolving\Entity\ResolverTask;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineMunicipalitiesIdsResolver implements TaskResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function canResolveTasks(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::MUNICIPALITIES_CODES === $type;
    }

    public function resolveTasks(ResolverJobIdentifier $job): void
    {
        $resultTable = $this->entityManager->getClassMetadata(ResolverResult::class)->getTableName();
        $taskTable = $this->entityManager->getClassMetadata(ResolverTask::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$resultTable} (id, job_id, confidence, match_type, building_id, building_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), t.job_id, t.confidence, t.match_type, b.building_id, b.id, t.additional_data FROM {$taskTable} t " .
            "LEFT JOIN {$buildingEntranceTable} b ON t.matching_municipality_code = b.municipality_code WHERE t.job_id = :jobId";

        $this->entityManager->getConnection()->executeStatement($sql, ['jobId' => $job->id]);
    }
}
