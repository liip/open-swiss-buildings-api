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
        $valuePlaceholders = [
            'id' => ':id',
            'job_id' => ':job_id',
            'confidence' => ':confidence',
            'match_type' => ':match_type',
            'matching_unique_hash' => ':matching_unique_hash',
            'matching_building_id' => ':matching_building_id',
            'matching_municipality_code' => ':matching_municipality_code',
            'matching_entrance_id' => ':matching_entrance_id',
            // The GeoJson must contain the CRS information about the coordinate system used in it, and we must
            // transform and store it with the WGS84 coordinate system
            // This column only accepts 2D geometries, so we need to use `ST_Force2D()` function on the data
            'matching_geo_json' => 'ST_Force2D(ST_Transform(ST_GeomFromGeoJSON(:matching_geo_json), ' . SRIDEnum::WGS84->value . '))',
            'additional_data' => ':additional_data',
        ];

        $sql = "INSERT INTO {$this->getClassMetadata()->getTableName()} AS t " .
            '(' . implode(',', array_keys($valuePlaceholders)) . ') ' .
            'VALUES (' . implode(',', $valuePlaceholders) . ') ' .
            'ON CONFLICT (job_id, matching_unique_hash) ' .
            'DO UPDATE SET confidence = LEAST(t.confidence, excluded.confidence), additional_data = t.additional_data::jsonb || excluded.additional_data::jsonb';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        try {
            foreach ($tasks as $task) {
                $stmt->bindValue('id', $task->id);
                $stmt->bindValue('job_id', $task->jobId);
                $stmt->bindValue('confidence', $task->confidence);
                $stmt->bindValue('match_type', $task->matchType);
                $stmt->bindValue('matching_unique_hash', $this->buildMatchingUniqueHash($task));
                $stmt->bindValue('matching_building_id', $task->matchingBuildingId);
                $stmt->bindValue('matching_municipality_code', $task->matchingMunicipalityCode);
                $stmt->bindValue('matching_entrance_id', $task->matchingEntranceId);
                $stmt->bindValue('matching_geo_json', $task->matchingGeoJson);
                $stmt->bindValue('additional_data', $task->additionalData->getAsList(), Types::JSON);
                $stmt->executeStatement();

                $this->eventDispatcher->dispatch(new ResolverTaskHasBeenCreated($task->id));
            }
        } catch (\Throwable $e) {
            throw ResolverJobFailedException::wrap($e);
        }
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
