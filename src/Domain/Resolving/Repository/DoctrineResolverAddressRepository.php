<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Repository;

use App\Domain\Resolving\Contract\Job\ResolverAddressReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressWriteRepositoryInterface;
use App\Domain\Resolving\Entity\ResolverAddress as ResolverAddressEntity;
use App\Domain\Resolving\Entity\ResolverAddressMatch as ResolverAddressMatchEntity;
use App\Domain\Resolving\Entity\ResolverAddressStreet as ResolverAddressStreetEntity;
use App\Domain\Resolving\Event\ResolverAddressHasBeenCreated;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Job\ResolverAddress;
use App\Domain\Resolving\Model\Job\WriteResolverAddress;
use App\Infrastructure\Address\AddressNormalizer;
use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ResolverAddressEntity>
 *
 * @method ResolverAddressEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResolverAddressEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResolverAddressEntity[]    findAll()
 * @method ResolverAddressEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class DoctrineResolverAddressRepository extends ServiceEntityRepository implements ResolverAddressReadRepositoryInterface, ResolverAddressWriteRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AddressNormalizer $normalizer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($registry, ResolverAddressEntity::class);
    }

    public function getNonMatchedAddresses(Uuid $jobId): iterable
    {
        $matchTable = $this->getEntityManager()->getClassMetadata(ResolverAddressMatchEntity::class)->getTableName();

        return $this->fetch(
            $jobId,
            "LEFT JOIN {$matchTable} m ON a.id = m.address_id",
            'm.id IS NULL',
        );
    }

    public function getNonMatchedAddressesWithoutStreet(Uuid $jobId): iterable
    {
        $matchTable = $this->getEntityManager()->getClassMetadata(ResolverAddressMatchEntity::class)->getTableName();
        $streetTable = $this->getEntityManager()->getClassMetadata(ResolverAddressStreetEntity::class)->getTableName();

        return $this->fetch(
            $jobId,
            "LEFT JOIN {$matchTable} m ON a.id = m.address_id LEFT JOIN {$streetTable} s ON a.id = s.address_id",
            'm.id IS NULL AND s.address_id IS NULL',
        );
    }

    public function store(iterable $addresses): void
    {
        $valuePlaceholders = [
            'id' => ':id',
            'job_id' => ':job_id',
            'unique_hash' => ':unique_hash',
            'street_name' => ':street_name',
            'street_name_normalized' => ':street_name_normalized',
            'street_house_number' => ':street_house_number',
            'street_house_number_suffix' => ':street_house_number_suffix',
            'street_house_number_suffix_normalized' => ':street_house_number_suffix_normalized',
            'postal_code' => ':postal_code',
            'locality' => ':locality',
            'locality_normalized' => ':locality_normalized',
            'additional_data' => ':additional_data',
            'range_from' => ':range_from',
            'range_to' => ':range_to',
            'range_type' => ':range_type',
        ];

        $sql = "INSERT INTO {$this->getClassMetadata()->getTableName()} AS t " .
            '(' . implode(',', array_keys($valuePlaceholders)) . ') ' .
            'VALUES (' . implode(',', $valuePlaceholders) . ') ' .
            'ON CONFLICT (job_id, unique_hash) ' .
            'DO UPDATE SET additional_data = t.additional_data::jsonb || excluded.additional_data::jsonb';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        /** @var WriteResolverAddress $address */
        foreach ($addresses as $address) {
            $stmt->bindValue('id', $address->id());
            $stmt->bindValue('job_id', $address->jobId());
            $stmt->bindValue('unique_hash', $address->uniqueHash());
            $stmt->bindValue('street_name', $address->streetName() ?? '');
            $stmt->bindValue('street_name_normalized', null !== $address->streetName() ? $this->normalizer->normalize($address->streetName()) : '');
            $stmt->bindValue('street_house_number', $address->houseNumber() ?? 0);
            $stmt->bindValue('street_house_number_suffix', $address->houseNumberSuffix() ?? '');
            $stmt->bindValue('street_house_number_suffix_normalized', $this->normalizer->normalize($address->houseNumberSuffix() ?? ''));
            $stmt->bindValue('postal_code', $address->postalCode() ?: '');
            $stmt->bindValue('locality', $address->locality() ?: '');
            $stmt->bindValue('locality_normalized', $this->normalizer->normalize($address->locality() ?: ''));
            $stmt->bindValue('additional_data', $address->additionalData()->getAsList(), Types::JSON);
            $stmt->bindValue('range_from', $address->rangeFrom());
            $stmt->bindValue('range_to', $address->rangeTo());
            $stmt->bindValue('range_type', $address->rangeType()?->value);
            $stmt->executeStatement();

            $this->eventDispatcher->dispatch(new ResolverAddressHasBeenCreated($address->id()));
        }
    }

    public function deleteByJobId(Uuid $jobId): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'DELETE FROM ' . ResolverAddressEntity::class . ' t ' .
                'WHERE t.job = :jobId',
            )
            ->setParameter('jobId', $jobId)
        ;

        return $query->execute();
    }

    /**
     * @return iterable<ResolverAddress>
     */
    private function fetch(Uuid $jobId, string $joins, string $conditions): iterable
    {
        $addressTable = $this->getEntityManager()->getClassMetadata(ResolverAddressEntity::class)->getTableName();

        $sql = 'SELECT a.id, a.job_id, a.street_name, a.street_house_number, a.street_house_number_suffix, a.postal_code, a.locality, a.additional_data' .
            " FROM {$addressTable} a {$joins}" .
            " WHERE a.job_id = :jobId AND {$conditions}";

        foreach ($this->getEntityManager()->getConnection()->iterateAssociative($sql, ['jobId' => $jobId]) as $row) {
            $number = null;
            if (0 !== $row['street_house_number'] || '' !== $row['street_house_number_suffix']) {
                $number = new StreetNumber($row['street_house_number'], $row['street_house_number_suffix']);
            }
            $street = null;
            if ('' !== $row['street_name'] || null !== $number) {
                $street = new Street($row['street_name'], $number);
            }

            yield new ResolverAddress(
                id: Uuid::fromString($row['id']),
                jobId: Uuid::fromString($row['job_id']),
                street: $street,
                postalCode: $row['postal_code'] ?: null,
                locality: $row['locality'] ?: null,
                additionalData: AdditionalData::createFromList(json_decode($row['additional_data'], true, 512, \JSON_THROW_ON_ERROR)),
            );
        }
    }
}
