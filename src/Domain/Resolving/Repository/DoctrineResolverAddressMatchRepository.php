<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\Resolving\Contract\Job\ResolverAddressMatchWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverAddress as ResolverAddressEntity;
use App\Domain\Resolving\Entity\ResolverAddressMatch as ResolverAddressMatchEntity;
use App\Domain\Resolving\Event\ResolverAddressHasMatched;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverAddressMatchEntity>
 */
final class DoctrineResolverAddressMatchRepository extends ServiceEntityRepository implements ResolverAddressMatchWriteRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($registry, ResolverAddressMatchEntity::class);
    }

    public function store(iterable $matches): void
    {
        $valuePlaceholders = [
            'id' => ':id',
            'address_id' => ':address_id',
            'confidence' => ':confidence',
            'match_type' => ':match_type',
            'matching_building_id' => ':matching_building_id',
            'matching_entrance_id' => ':matching_entrance_id',
            'additional_data' => ':additional_data',
        ];

        $sql = "INSERT INTO {$this->getClassMetadata()->getTableName()} AS m " .
            '(' . implode(',', array_keys($valuePlaceholders)) . ') ' .
            'VALUES (' . implode(',', $valuePlaceholders) . ')';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        foreach ($matches as $match) {
            $stmt->bindValue('id', Uuid::v7());
            $stmt->bindValue('address_id', $match->id);
            $stmt->bindValue('confidence', $match->confidence);
            $stmt->bindValue('match_type', $match->matchType);
            $stmt->bindValue('matching_building_id', $match->matchingBuildingId);
            $stmt->bindValue('matching_entrance_id', $match->matchingEntranceId);
            $stmt->bindValue('additional_data', $match->additionalData->getAsList(), Types::JSON);
            $stmt->executeStatement();

            $this->eventDispatcher->dispatch(new ResolverAddressHasMatched($match->id));
        }
    }

    public function deleteByJobId(Uuid $jobId): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverAddressMatchEntity::class . ' m ' .
                'WHERE IDENTITY(m.address) IN (SELECT a.id FROM ' . ResolverAddressEntity::class . ' a WHERE a.job = :jobId)',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->execute();
    }
}
