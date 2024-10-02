<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\RangeAddressTypeEnum;
use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStreetWithRangeMatcher
{
    public const string TYPE_STREET_EXACT_W_RANGE = 'streetNumberRange';
    public const string TYPE_STREET_EXACT_W_RANGE_SUFFIX = 'streetNumberSuffixRange';
    public const string TYPE_STREET_EXACT_NORMALIZED_W_RANGE = 'streetNormalizedNumberRange';
    public const string TYPE_STREET_EXACT_NORMALIZED_W_SUFFIX_RANGE = 'streetNormalizedNumberSuffixRange';

    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * @param int<0, 100> $confidence
     */
    public function matchStreetExactNumberRange(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchStreetOn(
            $job,
            $confidence,
            self::TYPE_STREET_EXACT_W_RANGE,
            'a.street_name = b.%street_name% AND a.postal_code = b.postal_code AND a.locality = b.locality',
            RangeAddressTypeEnum::HOUSE_NUMBER,
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchStreetExactNumberSuffixRange(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchStreetOn(
            $job,
            $confidence,
            self::TYPE_STREET_EXACT_W_RANGE_SUFFIX,
            'a.street_name = b.%street_name% AND a.postal_code = b.postal_code AND a.locality = b.locality',
            RangeAddressTypeEnum::HOUSE_NUMBER_SUFFIX,
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchStreetNormalizedNumberRange(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchStreetOn(
            $job,
            $confidence,
            self::TYPE_STREET_EXACT_NORMALIZED_W_RANGE,
            'a.street_name_normalized = b.%street_name%_normalized AND a.postal_code = b.postal_code AND a.locality_normalized = b.locality_normalized',
            RangeAddressTypeEnum::HOUSE_NUMBER,
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    public function matchStreetNormalizedNumberSuffixRange(ResolverJobIdentifier $job, int $confidence): void
    {
        $this->matchStreetOn(
            $job,
            $confidence,
            self::TYPE_STREET_EXACT_NORMALIZED_W_SUFFIX_RANGE,
            'a.street_name_normalized = b.%street_name%_normalized AND a.postal_code = b.postal_code AND a.locality_normalized = b.locality_normalized',
            RangeAddressTypeEnum::HOUSE_NUMBER_SUFFIX,
        );
    }

    /**
     * @param int<0, 100> $confidence
     */
    private function matchStreetOn(ResolverJobIdentifier $job, int $confidence, string $matchType, string $condition, RangeAddressTypeEnum $rangeType): void
    {
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();

        $condition .= match ($rangeType) {
            RangeAddressTypeEnum::HOUSE_NUMBER => ' AND b.street_house_number::text BETWEEN a.range_from AND a.range_to',
            RangeAddressTypeEnum::HOUSE_NUMBER_SUFFIX => ' AND b.street_house_number = a.street_house_number AND b.street_house_number_suffix_normalized BETWEEN a.range_from AND a.range_to',
        };

        $onCondition = '(' .
            str_replace('%street_name%', 'street_name', $condition) .
            ') OR (' .
            str_replace('%street_name%', 'street_name_abbreviated', $condition) .
            ')';

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$matchTable} AS t (id, address_id, confidence, match_type, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.id, :confidence, :matchType, b.building_id, b.entrance_id, a.additional_data FROM {$addressTable} a " .
            "LEFT JOIN {$matchTable} at ON a.id = at.address_id " .
            "INNER JOIN {$buildingEntranceTable} b ON {$onCondition} " .
            "WHERE a.job_id = :jobId AND at.id IS NULL AND a.range_type = '{$rangeType->value}'";

        $this->entityManager->getConnection()->executeStatement($sql, [
            'jobId' => $job->id,
            'confidence' => $confidence,
            'matchType' => $matchType,
        ]);
    }
}
