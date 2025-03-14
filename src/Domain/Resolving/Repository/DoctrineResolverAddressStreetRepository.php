<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\Resolving\Contract\Job\ResolverAddressStreetWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverAddress as ResolverAddressEntity;
use App\Domain\Resolving\Entity\ResolverAddressStreet as ResolverAddressStreetEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverAddressStreetEntity>
 */
final class DoctrineResolverAddressStreetRepository extends ServiceEntityRepository implements ResolverAddressStreetWriteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResolverAddressStreetEntity::class);
    }

    public function store(iterable $addressStreets): void
    {
        $valuePlaceholders = [
            'address_id' => ':address_id',
            'street_id' => ':street_id',
            'confidence' => ':confidence',
            'match_type' => ':match_type',
        ];

        $sql = "INSERT INTO {$this->getClassMetadata()->getTableName()} AS m " .
            '(' . implode(',', array_keys($valuePlaceholders)) . ') ' .
            'VALUES (' . implode(',', $valuePlaceholders) . ')';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        foreach ($addressStreets as $addressStreet) {
            $stmt->bindValue('address_id', $addressStreet->addressId);
            $stmt->bindValue('street_id', $addressStreet->streetId);
            $stmt->bindValue('confidence', $addressStreet->confidence);
            $stmt->bindValue('match_type', $addressStreet->matchType);
            $stmt->executeStatement();
        }
    }

    public function deleteByJobId(Uuid $jobId): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverAddressStreetEntity::class . ' m ' .
                'WHERE IDENTITY(m.address) IN (SELECT a.id FROM ' . ResolverAddressEntity::class . ' a WHERE a.job = :jobId)',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->execute();
    }
}
