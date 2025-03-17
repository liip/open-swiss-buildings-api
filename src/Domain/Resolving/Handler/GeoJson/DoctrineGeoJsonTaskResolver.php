<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\GeoJson;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Contract\Job\ResolverTaskReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\TaskResolverInterface;
use App\Domain\Resolving\Entity\ResolverResult;
use App\Domain\Resolving\Entity\ResolverTask;
use App\Domain\Resolving\Exception\ResolverJobFailedException;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineGeoJsonTaskResolver implements TaskResolverInterface
{
    // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
    private const array COLUMNS = [
        'id' => 'gen_random_uuid()',
        'job_id' => 'tasks.job_id',
        'confidence' => 'tasks.confidence',
        'match_type' => 'tasks.match_type',
        'country_code' => 'building.country_code',
        'building_id' => 'building.building_id',
        'entrance_id' => 'building.entrance_id',
        'building_entrance_id' => 'building.id',
        'additional_data' => 'tasks.additional_data',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResolverTaskReadRepositoryInterface $taskReadRepository,
        private LoggerInterface $logger,
    ) {}

    public function canResolveTasks(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::GEO_JSON === $type;
    }

    public function resolveTasks(ResolverJobIdentifier $job): void
    {
        $totalItems = 0;
        $this->logger->debug('Resolving for Job {job_id}: starting', ['job_id' => (string) $job->id]);

        try {
            foreach ($this->taskReadRepository->getTasksIds($job->id) as $taskId) {
                $totalItems += $this->resolveTask($taskId);
            }
        } catch (\Throwable $e) {
            throw ResolverJobFailedException::wrap($e);
        }

        $this->logger->debug('Resolving for Job {job_id}: done, {count} items processed', [
            'job_id' => (string) $job->id,
            'count' => $totalItems,
        ]);
    }

    private function resolveTask(Uuid $taskId): int
    {
        $preparedStatement = $this->getPreparedInsertSqlStatement();
        $preparedStatement->bindValue('taskId', $taskId);
        $count = $preparedStatement->executeStatement();
        $this->logger->debug('Resolved Task {task_id}: {count} items processed', [
            'task_id' => (string) $taskId,
            'count' => $count,
        ]);

        return $count;
    }

    private function getPreparedInsertSqlStatement(): Statement
    {
        /** @var Statement|null $prepared */
        static $prepared = null;
        if (null === $prepared) {
            $resultTable = $this->entityManager->getClassMetadata(ResolverResult::class)->getTableName();
            $sql = "INSERT INTO {$resultTable} AS results (" . implode(',', array_keys(self::COLUMNS)) . ') ' .
                $this->buildSelectQuery(self::COLUMNS) .
                ' WHERE tasks.id = :taskId' .
                ' ON CONFLICT (job_id, country_code, building_entrance_id) ' .
                ' DO UPDATE SET additional_data = results.additional_data::jsonb || excluded.additional_data::jsonb';

            $prepared = $this->entityManager->getConnection()->prepare($sql);
        }

        return $prepared;
    }

    /**
     * @param non-empty-array<string, non-empty-string> $columns
     */
    private function buildSelectQuery(array $columns): string
    {
        $taskTable = $this->entityManager->getClassMetadata(ResolverTask::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        return 'SELECT ' . implode(',', $columns) .
            " FROM {$taskTable} tasks" .
            " LEFT JOIN {$buildingEntranceTable} building ON ST_CONTAINS(tasks.matching_geo_json, building.geo_coordinates_wgs84)";
    }
}
