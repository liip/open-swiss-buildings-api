<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineExactAddressMatcher
{
    public const string TYPE_EXACT = 'exact';
    public const string TYPE_EXACT_NORMALIZED = 'exactNormalized';

    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * @param int<0, 100> $confidence
     */
    public function matchExactly(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchOn(
            $job,
            $confidence,
            self::TYPE_EXACT,
            '(a.street_name = b.%street_name% AND a.street_house_number = b.street_house_number AND a.street_house_number_suffix = b.street_house_number_suffix AND a.postal_code = b.postal_code AND a.locality = b.locality)',
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchExactlyNormalized(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchOn(
            $job,
            $confidence,
            self::TYPE_EXACT_NORMALIZED,
            '(a.street_name_normalized = b.%street_name%_normalized AND a.street_house_number = b.street_house_number AND a.street_house_number_suffix_normalized = b.street_house_number_suffix_normalized AND a.postal_code = b.postal_code AND a.locality_normalized = b.locality_normalized)',
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    private function matchOn(
        ResolverJobIdentifier $job,
        int $confidence,
        string $matchType,
        string $condition,
    ): void {
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        $onCondition = "({$condition})";
        if (str_contains($condition, '%street_name%')) {
            $onCondition =
                str_replace('%street_name%', 'street_name', $condition) .
                ' OR ' .
                str_replace('%street_name%', 'street_name_abbreviated', $condition);
        }

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$matchTable} AS t (id, address_id, confidence, match_type, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.id, :confidence, :matchType, b.building_id, b.entrance_id, a.additional_data FROM {$addressTable} a " .
            "LEFT JOIN {$matchTable} at ON a.id = at.address_id " .
            "INNER JOIN {$buildingEntranceTable} b ON {$onCondition} " .
            'WHERE a.job_id = :jobId AND at.id IS NULL AND a.range_type IS NULL';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'jobId' => $job->id,
            'confidence' => $confidence,
            'matchType' => $matchType,
        ]);
    }
}
