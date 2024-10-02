<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNothingMatcher
{
    public const string TYPE_NOTHING = 'nothing';

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function matchNothing(ResolverJobIdentifier $job): void
    {
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$matchTable} AS t (id, address_id, confidence, match_type, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.id, 0, :matchType, NULL, NULL, a.additional_data FROM {$addressTable} a " .
            "LEFT JOIN {$matchTable} at ON a.id = at.address_id " .
            'WHERE a.job_id = :jobId AND at.id IS NULL';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'jobId' => $job->id,
            'matchType' => self::TYPE_NOTHING,
        ]);
    }
}
