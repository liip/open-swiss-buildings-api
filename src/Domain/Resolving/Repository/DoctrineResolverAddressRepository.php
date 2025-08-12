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
 */
final class DoctrineResolverAddressRepository extends ServiceEntityRepository implements ResolverAddressReadRepositoryInterface, ResolverAddressWriteRepositoryInterface
{
    // postgres is limited to 65k parameters, and we have 15 per row,
    private const int BATCH_SIZE = 4300;

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
        $columns = [
            'id' => '?',
            'job_id' => '?',
            'unique_hash' => '?',
            'street_name' => '?',
            'street_name_normalized' => '?',
            'street_house_number' => '?',
            'street_house_number_suffix' => '?',
            'street_house_number_suffix_normalized' => '?',
            'postal_code' => '?',
            'locality' => '?',
            'locality_normalized' => '?',
            'additional_data' => '?',
            'range_from' => '?',
            'range_to' => '?',
            'range_type' => '?',
        ];
        $types = [Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::STRING, Types::JSON, Types::STRING, Types::STRING, Types::STRING];
        \assert(\count($types) === \count($columns)); /** @phpstan-ignore function.alreadyNarrowedType, identical.alwaysTrue */
        $sql = $this->buildInsertSql($columns, self::BATCH_SIZE);
        $typeMap = $this->buildTypes($types, self::BATCH_SIZE);
        $connection = $this->getEntityManager()->getConnection();

        $uniqueHashes = [];
        $batch = [];
        $addressIds = [];
        $batchCount = 0;

        /** @var WriteResolverAddress $address */
        foreach ($addresses as $address) {
            $uniqueHash = $address->uniqueHash();
            if (\array_key_exists($uniqueHash, $uniqueHashes)) {
                $uniqueHashes[$uniqueHash][] = $address;
                continue;
            }
            $uniqueHashes[$uniqueHash] = [];

            $addressIds[] = $address->id();
            $batch[] = $address->id();
            $batch[] = $address->jobId();
            $batch[] = $uniqueHash;
            $batch[] = $address->streetName() ?? '';
            $batch[] = null !== $address->streetName() ? $this->normalizer->normalize($address->streetName()) : '';
            $batch[] = $address->houseNumber() ?? 0;
            $batch[] = $address->houseNumberSuffix() ?? '';
            $batch[] = $this->normalizer->normalize($address->houseNumberSuffix() ?? '');
            $batch[] = $address->postalCode() ?: '';
            $batch[] = $address->locality() ?: '';
            $batch[] = $this->normalizer->normalize($address->locality() ?: '');
            $batch[] = $address->additionalData()->getAsList();
            $batch[] = $address->rangeFrom();
            $batch[] = $address->rangeTo();
            $batch[] = $address->rangeType()?->value;

            if (self::BATCH_SIZE === ++$batchCount) {
                $connection->executeStatement($sql, $batch, $typeMap);
                foreach ($addressIds as $addressId) {
                    $this->eventDispatcher->dispatch(new ResolverAddressHasBeenCreated($addressId));
                }
                $batch = [];
                $batchCount = 0;
                $addressIds = [];
            }
        }
        if ($batchCount > 0) {
            $sql = $this->buildInsertSql($columns, $batchCount);
            $connection->executeStatement($sql, $batch, $this->buildTypes($types, $batchCount));
            foreach ($addressIds as $addressId) {
                $this->eventDispatcher->dispatch(new ResolverAddressHasBeenCreated($addressId));
            }
        }
        $hashClashes = array_filter($uniqueHashes);
        if (0 < \count($hashClashes)) {
            $updateStmt = $connection->prepare($this->buildUpdateSql());
            foreach ($hashClashes as $matchingUniqueHash => $clash) {
                $updateStmt->bindValue('data', array_merge(...array_map(static fn(WriteResolverAddress $address): array => $address->additionalData()->getAsList(), $clash)), Types::JSON);
                $updateStmt->bindValue('hash', $matchingUniqueHash);
                $updateStmt->executeStatement();
            }
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
            'UPDATE %s SET additional_data = additional_data::jsonb || :data WHERE unique_hash = :hash',
            $this->getClassMetadata()->getTableName(),
        );
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
