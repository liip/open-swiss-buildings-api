<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\Resolving\Contract\Data\ResolverJobRawDataRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverJob as ResolverJobEntity;
use App\Domain\Resolving\Event\ResolverJobWasDeleted;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverJobEntity>
 */
final class DoctrineResolverJobRepository extends ServiceEntityRepository implements ResolverJobWriteRepositoryInterface, ResolverJobReadRepositoryInterface, ResolverJobRawDataRepositoryInterface, ResolverMetadataWriteRepositoryInterface
{
    private const string DQL_NEW_JOB = 'NEW ' . ResolverJob::class . '(j.id, j.type, j.metadata, j.state, j.failure, j.createdAt, j.modifiedAt, j.expiresAt)';

    public function __construct(
        ManagerRegistry $registry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($registry, ResolverJobEntity::class);
    }

    public function add(
        ResolverTypeEnum $type,
        $data,
        ResolverMetadata $metadata,
    ): Uuid {
        $entity = new ResolverJobEntity(
            $type,
            $data,
            $metadata->toArray(),
            $this->clock->now(),
        );

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->id;
    }

    public function markJobAsCreated(Uuid $id): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsCreated($this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function markJobAsPreparing(Uuid $id): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsPreparing($this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function markJobAsReady(Uuid $id): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsReady($this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function markJobAsResolving(Uuid $id): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsResolving($this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function markJobAsCompleted(Uuid $id): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsCompleted($this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function markJobAsFailed(Uuid $id, ResolverJobFailure $failure): void
    {
        $entity = $this->getEntity($id);
        $entity->markAsFailed($this->clock->now(), $failure);
        $this->getEntityManager()->flush();
    }

    public function markJobAsTemporarilyFailed(Uuid $id, ResolverJobFailure $failure): void
    {
        $entity = $this->getEntity($id);
        $entity->flagAsTemporarilyFailed($this->clock->now(), $failure);
        $this->getEntityManager()->flush();
    }

    public function updateMetadata(Uuid $id, ResolverMetadata $metadata): void
    {
        $entity = $this->getEntity($id);
        $entity->setMetadata($metadata, $this->clock->now());
        $this->getEntityManager()->flush();
    }

    public function delete(Uuid $id): void
    {
        $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverJobEntity::class . ' j WHERE j.id = :id',
            )
            ->setParameter('id', $id)
            ->execute()
        ;

        $this->eventDispatcher->dispatch(new ResolverJobWasDeleted($id));
    }

    public function getJobs(): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ' . self::DQL_NEW_JOB . ' FROM ' . ResolverJobEntity::class . ' j',
            )
        ;

        return $query->toIterable();
    }

    public function getExpiredJobs(\DateTimeInterface $now): iterable
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT NEW ' . ResolverJobIdentifier::class . '(j.id, j.type) FROM ' . ResolverJobEntity::class . ' j WHERE j.expiresAt <= :now',
            )
            ->setParameter('now', $now)
        ;

        return $query->toIterable();
    }

    public function getJobIdentifier(Uuid $id): ResolverJobIdentifier
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT NEW ' . ResolverJobIdentifier::class . '(j.id, j.type) FROM ' . ResolverJobEntity::class . ' j WHERE j.id = :id',
            )
            ->setParameter('id', $id)
        ;

        $job = $query->getOneOrNullResult();
        if (null === $job) {
            throw new ResolverJobNotFoundException($id);
        }

        return $job;
    }

    public function getJobInfo(Uuid $id): ResolverJob
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ' . self::DQL_NEW_JOB . ' FROM ' . ResolverJobEntity::class . ' j WHERE j.id = :id',
            )
            ->setParameter('id', $id)
        ;

        $job = $query->getOneOrNullResult();
        if (null === $job) {
            throw new ResolverJobNotFoundException($id);
        }

        return $job;
    }

    public function getRawData(Uuid $id): ResolverJobRawData
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT NEW ' . ResolverJobRawData::class . '(j.id, j.type, j.data, j.metadata) FROM ' . ResolverJobEntity::class . ' j WHERE j.id = :id',
            )
            ->setParameter('id', $id)
        ;
        $result = $query->getOneOrNullResult();
        if (null === $result) {
            throw new ResolverJobNotFoundException($id);
        }

        return $result;
    }

    private function getEntity(Uuid $id): ResolverJobEntity
    {
        $entity = $this->find($id);
        if (null === $entity) {
            throw new ResolverJobNotFoundException($id);
        }

        return $entity;
    }
}
