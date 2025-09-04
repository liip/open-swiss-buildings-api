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
use App\Infrastructure\Doctrine\BatchInsertStatementBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
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
    private const int INSERT_BATCH_SIZE = 4300;

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
        $connection = $this->getEntityManager()->getConnection();

        $columnDefinitions = [
            'id' => ':id%i%',
            'job_id' => ':job_id%i%',
            'unique_hash' => ':unique_hash%i%',
            'street_name' => ':street_name%i%',
            'street_name_normalized' => ':street_name_normalized%i%',
            'street_house_number' => ':street_house_number%i%',
            'street_house_number_suffix' => ':street_house_number_suffix%i%',
            'street_house_number_suffix_normalized' => ':street_house_number_suffix_normalized%i%',
            'postal_code' => ':postal_code%i%',
            'locality' => ':locality%i%',
            'locality_normalized' => ':locality_normalized%i%',
            'additional_data' => ':additional_data%i%',
            'range_from' => ':range_from%i%',
            'range_to' => ':range_to%i%',
            'range_type' => ':range_type%i%',
        ];
        $batchSql = BatchInsertStatementBuilder::generate(
            $this->getClassMetadata()->getTableName(),
            self::INSERT_BATCH_SIZE,
            $columnDefinitions,
        );
        $batchStmt = $connection->prepare($batchSql);

        $bindValues = function (Statement $stmt, int $i, WriteResolverAddress $address): void {
            $stmt->bindValue("id{$i}", $address->id());
            $stmt->bindValue("job_id{$i}", $address->jobId());
            $stmt->bindValue("unique_hash{$i}", $address->uniqueHash());
            $stmt->bindValue("street_name{$i}", $address->streetName() ?? '');
            $stmt->bindValue("street_name_normalized{$i}", null !== $address->streetName() ? $this->normalizer->normalize($address->streetName()) : '');
            $stmt->bindValue("street_house_number{$i}", $address->houseNumber() ?? 0);
            $stmt->bindValue("street_house_number_suffix{$i}", $address->houseNumberSuffix() ?? '');
            $stmt->bindValue("street_house_number_suffix_normalized{$i}", $this->normalizer->normalize($address->houseNumberSuffix() ?? ''));
            $stmt->bindValue("postal_code{$i}", $address->postalCode() ?: '');
            $stmt->bindValue("locality{$i}", $address->locality() ?: '');
            $stmt->bindValue("locality_normalized{$i}", $this->normalizer->normalize($address->locality() ?: ''));
            $stmt->bindValue("additional_data{$i}", $address->additionalData()->getAsList(), Types::JSON);
            $stmt->bindValue("range_from{$i}", $address->rangeFrom());
            $stmt->bindValue("range_to{$i}", $address->rangeTo());
            $stmt->bindValue("range_type{$i}", $address->rangeType()?->value);
        };

        $uniqueHashes = [];
        $batchEntries = [];
        $i = 1;

        /** @var WriteResolverAddress $address */
        foreach ($addresses as $address) {
            $uniqueHash = $address->uniqueHash();
            if (\array_key_exists($uniqueHash, $uniqueHashes)) {
                $uniqueHashes[$uniqueHash][] = $address;
                continue;
            }
            $uniqueHashes[$uniqueHash] = [];
            $batchEntries[] = $address;

            if (self::INSERT_BATCH_SIZE === $i) {
                $i = 1;
                foreach ($batchEntries as $entry) {
                    $bindValues($batchStmt, $i++, $entry);
                }
                $batchStmt->executeStatement();
                foreach ($batchEntries as $insertedAddress) {
                    $this->eventDispatcher->dispatch(new ResolverAddressHasBeenCreated($insertedAddress->id()));
                }
                $batchEntries = [];
                $i = 0;
            }
            ++$i;
        }

        if ([] !== $batchEntries) {
            $batchSql = BatchInsertStatementBuilder::generate(
                $this->getClassMetadata()->getTableName(),
                \count($batchEntries),
                $columnDefinitions,
            );
            $batchStmt = $connection->prepare($batchSql);
            $i = 1;
            foreach ($batchEntries as $address) {
                $bindValues($batchStmt, $i++, $address);
            }
            $batchStmt->executeStatement();

            foreach ($batchEntries as $address) {
                $this->eventDispatcher->dispatch(new ResolverAddressHasBeenCreated($address->id()));
            }
        }
        $hashClashes = array_filter($uniqueHashes);
        if (0 < \count($hashClashes)) {
            $connection->executeStatement('SET enable_seqscan = FALSE');
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
