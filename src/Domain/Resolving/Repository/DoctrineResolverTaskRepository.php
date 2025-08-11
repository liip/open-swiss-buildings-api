<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\Resolving\Contract\Job\ResolverTaskReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverTask as ResolverTaskEntity;
use App\Domain\Resolving\Event\ResolverTaskHasBeenCreated;
use App\Domain\Resolving\Exception\ResolverJobFailedException;
use App\Domain\Resolving\Model\Job\ResolverTask;
use App\Domain\Resolving\Model\Job\WriteResolverTask;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Pagination;
use App\Infrastructure\PostGis\SRIDEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverTaskEntity>
 */
final class DoctrineResolverTaskRepository extends ServiceEntityRepository implements
    ResolverTaskWriteRepositoryInterface,
    ResolverTaskReadRepositoryInterface
{
    private const string DQL_NEW_TASK = 'NEW ' . ResolverTask::class . '(t.id, IDENTITY(t.job), t.confidence, t.matchType, t.matchingBuildingId, t.matchingMunicipalityCode, t.matchingEntranceId, t.additionalData)';
    // postgres is limited to 65k parameters, and we have 10 per row,
    private const int BATCH_SIZE = 6500;

    public function __construct(
        ManagerRegistry $registry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($registry, ResolverTaskEntity::class);
    }

    public function deleteByJobId(Uuid $jobId): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverTaskEntity::class . ' t ' .
                'WHERE t.job = :jobId',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->execute();
    }

    public function store(iterable $tasks): void
    {
        $columns = [
            'id' => '?',
            'job_id' => '?',
            'confidence' => '?',
            'match_type' => '?',
            'matching_unique_hash' => '?',
            'matching_building_id' => '?',
            'matching_municipality_code' => '?',
            'matching_entrance_id' => '?',
            // The GeoJson must contain the CRS information about the coordinate system used in it, and we must
            // transform and store it with the WGS84 coordinate system
            // This column only accepts 2D geometries, so we need to use `ST_Force2D()` function on the data
            'matching_geo_json' => 'ST_Force2D(ST_Transform(ST_GeomFromGeoJSON(?), ' . SRIDEnum::WGS84->value . '))',
            'additional_data' => '?',
        ];
        $types = [Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::JSON];
        \assert(\count($types) === \count($columns)); /** @phpstan-ignore function.alreadyNarrowedType, identical.alwaysTrue */
        $sql = $this->buildInsertSql($columns, self::BATCH_SIZE);
        $typeMap = $this->buildTypes($types, self::BATCH_SIZE);
        $connection = $this->getEntityManager()->getConnection();

        $matchingUniqueHashes = [];

        try {
            $batch = [];
            $taskIds = [];
            $batchCount = 0;
            foreach ($tasks as $task) {
                $matchingUniqueHash = $this->buildMatchingUniqueHash($task);
                if (\array_key_exists($matchingUniqueHash, $matchingUniqueHashes)) {
                    $matchingUniqueHashes[$matchingUniqueHash][] = $task;
                    continue;
                }
                $matchingUniqueHashes[$matchingUniqueHash] = [];
                $taskIds[] = $task->id;
                $batch[] = $task->id;
                $batch[] = $task->jobId;
                $batch[] = $task->confidence;
                $batch[] = $task->matchType;
                $batch[] = $matchingUniqueHash;
                $batch[] = $task->matchingBuildingId;
                $batch[] = $task->matchingMunicipalityCode;
                $batch[] = $task->matchingEntranceId;
                $batch[] = $task->matchingGeoJson;
                $batch[] = $task->additionalData->getAsList();

                if (self::BATCH_SIZE === ++$batchCount) {
                    $connection->executeStatement($sql, $batch, $typeMap);
                    foreach ($taskIds as $taskId) {
                        $this->eventDispatcher->dispatch(new ResolverTaskHasBeenCreated($taskId));
                    }
                    $batch = [];
                    $batchCount = 0;
                    $taskIds = [];
                }
            }
            if ($batchCount > 0) {
                $sql = $this->buildInsertSql($columns, $batchCount);
                $connection->executeStatement($sql, $batch, $this->buildTypes($types, $batchCount));
                foreach ($taskIds as $taskId) {
                    $this->eventDispatcher->dispatch(new ResolverTaskHasBeenCreated($taskId));
                }
            }
            $hashClashes = array_filter($matchingUniqueHashes);
            if (0 < \count($hashClashes)) {
                $updateStmt = $connection->prepare($this->buildUpdateSql());
                foreach ($hashClashes as $matchingUniqueHash => $clash) {
                    $updateStmt->bindValue('confidence', min(array_map(static fn(WriteResolverTask $task): int => $task->confidence, $clash)));
                    $updateStmt->bindValue('data', array_merge(...array_map(static fn(WriteResolverTask $task): array => $task->additionalData->getAsList(), $clash)), Types::JSON);
                    $updateStmt->bindValue('hash', $matchingUniqueHash);
                    $updateStmt->executeStatement();
                }
            }

        } catch (\Throwable $e) {
            throw ResolverJobFailedException::wrap($e);
        }
    }

    /**
     * @param array<string, string> $columns
     */
    private function buildInsertSql(array $columns, int $rows): string
    {
        $placeholderRow = '(' . implode(',', $columns) . ')';

        return \sprintf(
            'INSERT INTO %s AS t (%s) VALUES %s',
            $this->getClassMetadata()->getTableName(),
            implode(',', array_keys($columns)),
            str_repeat("{$placeholderRow},", $rows - 1) . $placeholderRow,
        );
    }

    /**
     * @param array<string|null> $types
     *
     * @return array<string|null>
     */
    private function buildTypes(array $types, int $rows): array
    {
        return array_merge(...array_fill(0, $rows, $types));
    }

    private function buildUpdateSql(): string
    {
        return \sprintf(
            'UPDATE %s SET confidence = LEAST(confidence, :confidence), additional_data = additional_data::jsonb || :data WHERE matching_unique_hash = :hash',
            $this->getClassMetadata()->getTableName(),
        );
    }

    private function buildMatchingUniqueHash(WriteResolverTask $task): string
    {
        $data = match ($task->jobType) {
            ResolverTypeEnum::BUILDING_IDS => $task->matchingBuildingId,
            ResolverTypeEnum::MUNICIPALITIES_CODES => $task->matchingMunicipalityCode,
            ResolverTypeEnum::ADDRESS_SEARCH => $this->buildMatchingUniqueHashForAddressSearch($task),
            ResolverTypeEnum::GEO_JSON => $task->matchingGeoJson,
        };

        return hash('xxh3', $data ?? '');
    }

    private function buildMatchingUniqueHashForAddressSearch(WriteResolverTask $task): string
    {
        $data = '';
        if (null !== $task->matchingBuildingId) {
            $data .= $task->matchingBuildingId;
        }
        if (null !== $task->matchingEntranceId) {
            $data .= $task->matchingEntranceId;
        }

        if ('' === $data) {
            $data = implode(',', $task->additionalData->getAddressData());
        }

        return $data;
    }

    public function getTasks(Uuid $jobId): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ' . self::DQL_NEW_TASK . ' FROM ' . ResolverTaskEntity::class . ' t' .
                ' WHERE t.job = :jobId',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->toIterable();
    }

    public function getPaginatedTasks(Uuid $jobId, Pagination $pagination): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ' . self::DQL_NEW_TASK . ' FROM ' . ResolverTaskEntity::class . ' t' .
                ' WHERE t.job = :jobId',
            )
            ->setParameter('jobId', $jobId)
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset)
        ;

        return $query->toIterable();
    }

    public function getTasksIds(Uuid $jobId): iterable
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT NEW ' . Uuid::class . '(t.id)' .
                ' FROM ' . ResolverTaskEntity::class . ' t' .
                ' WHERE t.job = :jobId',
        )
            ->setParameter('jobId', $jobId)
        ;

        return $query->toIterable();
    }
}
