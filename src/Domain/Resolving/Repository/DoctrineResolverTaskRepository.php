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
use App\Infrastructure\Doctrine\BatchInsertStatementBuilder;
use App\Infrastructure\Pagination;
use App\Infrastructure\PostGis\SRIDEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
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
    private const int INSERT_BATCH_SIZE = 6500;

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
        $connection = $this->getEntityManager()->getConnection();

        $columnDefinitions = [
            'id' => ':id%i%',
            'job_id' => ':job_id%i%',
            'confidence' => ':confidence%i%',
            'match_type' => ':match_type%i%',
            'matching_unique_hash' => ':matching_unique_hash%i%',
            'matching_building_id' => ':matching_building_id%i%',
            'matching_municipality_code' => ':matching_municipality_code%i%',
            'matching_entrance_id' => ':matching_entrance_id%i%',
            // The GeoJson must contain the CRS information about the coordinate system used in it, and we must
            // transform and store it with the WGS84 coordinate system
            // This column only accepts 2D geometries, so we need to use `ST_Force2D()` function on the data
            'matching_geo_json' => 'ST_Force2D(ST_Transform(ST_GeomFromGeoJSON(:matching_geo_json%i%), ' . SRIDEnum::WGS84->value . '))',
            'additional_data' => ':additional_data%i%',
        ];
        $batchSql = BatchInsertStatementBuilder::generate(
            $this->getClassMetadata()->getTableName(),
            self::INSERT_BATCH_SIZE,
            $columnDefinitions,
        );
        $batchStmt = $connection->prepare($batchSql);

        $bindValues = function (Statement $stmt, int $i, WriteResolverTask $task): void {
            $stmt->bindValue("id{$i}", $task->id);
            $stmt->bindValue("job_id{$i}", $task->jobId);
            $stmt->bindValue("confidence{$i}", $task->confidence);
            $stmt->bindValue("match_type{$i}", $task->matchType);
            $stmt->bindValue("matching_unique_hash{$i}", $this->buildMatchingUniqueHash($task));
            $stmt->bindValue("matching_building_id{$i}", $task->matchingBuildingId);
            $stmt->bindValue("matching_municipality_code{$i}", $task->matchingMunicipalityCode);
            $stmt->bindValue("matching_entrance_id{$i}", $task->matchingEntranceId);
            $stmt->bindValue("matching_geo_json{$i}", $task->matchingGeoJson);
            $stmt->bindValue("additional_data{$i}", $task->additionalData->getAsList(), Types::JSON);
        };

        $matchingUniqueHashes = [];
        $batchEntries = [];
        $i = 1;

        try {
            foreach ($tasks as $task) {
                $matchingUniqueHash = $this->buildMatchingUniqueHash($task);
                if (\array_key_exists($matchingUniqueHash, $matchingUniqueHashes)) {
                    $matchingUniqueHashes[$matchingUniqueHash][] = $task;
                    continue;
                }
                $matchingUniqueHashes[$matchingUniqueHash] = [];
                $batchEntries[] = $task;

                if (self::INSERT_BATCH_SIZE === $i) {
                    $i = 1;
                    foreach ($batchEntries as $entry) {
                        $bindValues($batchStmt, $i++, $entry);
                    }
                    $batchStmt->executeStatement();
                    foreach ($batchEntries as $insertedTask) {
                        $this->eventDispatcher->dispatch(new ResolverTaskHasBeenCreated($insertedTask->id));
                    }
                    $batchEntries = [];
                    $i = 0;
                }
                ++$i;
            }
            if (\count($batchEntries) > 0) {
                $batchSql = BatchInsertStatementBuilder::generate(
                    $this->getClassMetadata()->getTableName(),
                    \count($batchEntries),
                    $columnDefinitions,
                );
                $batchStmt = $connection->prepare($batchSql);
                $i = 1;
                foreach ($batchEntries as $task) {
                    $bindValues($batchStmt, $i++, $task);
                }
                $batchStmt->executeStatement();
                foreach ($batchEntries as $task) {
                    $this->eventDispatcher->dispatch(new ResolverTaskHasBeenCreated($task->id));
                }
            }
            $hashClashes = array_filter($matchingUniqueHashes);
            if (0 < \count($hashClashes)) {
                $connection->executeStatement('SET enable_seqscan = FALSE');
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
