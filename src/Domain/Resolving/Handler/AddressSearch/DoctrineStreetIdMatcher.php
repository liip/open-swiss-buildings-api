<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Entity\ResolverAddressStreet;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStreetIdMatcher
{
    public const string TYPE_STREET_FULL = 'full';

    public const string TYPE_STREET_HOUSE_NUMBER_WITHOUT_SUFFIX = 'houseNumberWithoutSuffix';

    public const string TYPE_STREET_HOUSE_NUMBERS_WITH_SUFFIX = 'houseNumbersWithSuffix';

    public const string TYPE_STREET_HOUSE_NUMBERS_WITH_OTHER_SUFFIX = 'houseNumbersWithOtherSuffix';

    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * @param int<0, 100> $confidence
     */
    public function matchOnFullStreet(ResolverJobIdentifier $job, int $confidence): void
    {
        // Match on exact house number, including suffix
        $this->matchOnStreetId(
            $job,
            $confidence,
            self::TYPE_STREET_FULL,
            'a.street_house_number = b.street_house_number AND a.street_house_number_suffix = b.street_house_number_suffix',
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchWithoutSuffix(ResolverJobIdentifier $job, int $confidence): void
    {
        // Match on house number without suffix (in case input address has a suffix)
        $this->matchOnStreetId(
            $job,
            $confidence,
            self::TYPE_STREET_HOUSE_NUMBER_WITHOUT_SUFFIX,
            "a.street_house_number = b.street_house_number AND b.street_house_number_suffix = ''",
            "a.street_house_number != 0 AND a.street_house_number_suffix != ''",
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchWitSuffix(ResolverJobIdentifier $job, int $confidence): void
    {
        // Match on house numbers with suffixes (in case input address has no suffix)
        $this->matchOnStreetId(
            $job,
            $confidence,
            self::TYPE_STREET_HOUSE_NUMBERS_WITH_SUFFIX,
            "a.street_house_number = b.street_house_number AND b.street_house_number_suffix != ''",
            "a.street_house_number != 0 AND a.street_house_number_suffix = ''",
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchWithOtherSuffix(ResolverJobIdentifier $job, int $confidence): void
    {
        // Match on house numbers with suffixes (in case input address has other suffix)
        $this->matchOnStreetId(
            $job,
            $confidence,
            self::TYPE_STREET_HOUSE_NUMBERS_WITH_OTHER_SUFFIX,
            "a.street_house_number = b.street_house_number AND b.street_house_number_suffix != ''",
            "a.street_house_number != 0 AND a.street_house_number_suffix != ''",
        );
    }

    /**
     * @param int<0, 100> $confidenceDiff
     */
    private function matchOnStreetId(
        ResolverJobIdentifier $job,
        int $confidenceDiff,
        string $matchType,
        string $condition,
        ?string $requirement = null,
    ): void {
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();
        $streetTable = $this->entityManager->getClassMetadata(ResolverAddressStreet::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        $where = '';
        if (null !== $requirement) {
            $where = " AND {$requirement}";
        }

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$matchTable} AS t (id, address_id, confidence, match_type, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.id, s.confidence - :confidenceDiff, CONCAT(s.match_type, '-', :matchType::text), b.building_id, b.entrance_id, a.additional_data FROM {$addressTable} a " .
            "LEFT JOIN {$matchTable} at ON a.id = at.address_id " .
            "INNER JOIN {$streetTable} s ON a.id = s.address_id " .
            "INNER JOIN {$buildingEntranceTable} b ON s.street_id = b.street_id AND {$condition} " .
            "WHERE a.job_id = :jobId AND at.id IS NULL AND a.range_type IS NULL {$where}";

        $this->entityManager->getConnection()->executeStatement($sql, [
            'jobId' => $job->id,
            'confidenceDiff' => $confidenceDiff,
            'matchType' => $matchType,
        ]);
    }
}
