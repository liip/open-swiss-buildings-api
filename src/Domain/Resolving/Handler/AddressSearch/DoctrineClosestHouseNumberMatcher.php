<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\ResolverAddress;
use App\Domain\Resolving\Entity\ResolverAddressMatch;
use App\Domain\Resolving\Entity\ResolverAddressStreet;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineClosestHouseNumberMatcher
{
    public const string TYPE_STREET_CLOSEST_HOUSE_NUMBER = 'closestHouseNumber';

    /**
     * Intervals for matching on closest house numbers.
     *
     * Each entry specifies a maximum distance of the street house number.
     * Additionally, it defines a confidence as a difference to a certain base confidence.
     *
     * Higher distances should result in lower confidence.
     */
    private const array STREET_HOUSE_NUMBER_MATCH_INTERVALS = [
        ['max_number_distance' => 2, 'confidence_diff' => 20],
        ['max_number_distance' => 10, 'confidence_diff' => 30],
        ['max_number_distance' => 50, 'confidence_diff' => 40],
        ['max_number_distance' => 100, 'confidence_diff' => 50],
        ['max_number_distance' => 10000, 'confidence_diff' => 60],
    ];

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function matchClosestHouseNumber(ResolverJobIdentifier $job): void
    {
        $addressTable = $this->entityManager->getClassMetadata(ResolverAddress::class)->getTableName();
        $matchTable = $this->entityManager->getClassMetadata(ResolverAddressMatch::class)->getTableName();
        $streetTable = $this->entityManager->getClassMetadata(ResolverAddressStreet::class)->getTableName();
        $buildingEntranceTable = $this->entityManager->getClassMetadata(BuildingEntrance::class)->getTableName();

        // TODO PostgreSQL will generate UUIDv4 with gen_random_uuid(), switch to UUIDv7 with PostgreSQL version 17: https://commitfest.postgresql.org/43/4388/
        $sql = "INSERT INTO {$matchTable} AS t (id, address_id, confidence, match_type, matching_building_id, matching_entrance_id, additional_data) " .
            "SELECT gen_random_uuid(), a.id, s.confidence - :confidenceDiff, CONCAT(s.match_type, '-', :matchType::text), b.building_id, b.entrance_id, a.additional_data FROM {$addressTable} a " .
            "LEFT JOIN {$matchTable} at ON a.id = at.address_id " .
            "INNER JOIN {$streetTable} s ON a.id = s.address_id " .
            "INNER JOIN LATERAL (SELECT be.building_id, be.entrance_id FROM {$buildingEntranceTable} be WHERE " .
            's.street_id = be.street_id  AND abs(a.street_house_number - be.street_house_number) <= :distance ' .
            ' ORDER BY abs(a.street_house_number - be.street_house_number) LIMIT 1 ' .
            ') AS b ON 1=1 ' .
            'WHERE a.job_id = :jobId AND at.id IS NULL AND a.range_type IS NULL';

        foreach (self::STREET_HOUSE_NUMBER_MATCH_INTERVALS as ['max_number_distance' => $maxDistance, 'confidence_diff' => $confidenceDiff]) {
            $this->entityManager->getConnection()->executeStatement($sql, [
                'jobId' => $job->id,
                'confidenceDiff' => $confidenceDiff,
                'distance' => $maxDistance,
                'matchType' => self::TYPE_STREET_CLOSEST_HOUSE_NUMBER,
            ]);
        }
    }
}
