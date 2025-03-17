<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Model;

use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Domain\AddressSearch\Model\BuildingAddressScored;

final readonly class BuildingAddressEntity
{
    public const string INDEX_NAME = 'buildingAddress';

    public const string FIELD_JSON_MODEL = 'jsonModel';
    public const string FIELD_ID = 'id';
    public const string FIELD_COUNTRY_CODE = 'country';
    public const string FIELD_BUILDING_ID = 'buildingId';
    public const string FIELD_IMPORTED_AT = 'importedAt';
    public const string FIELD_FULL_ADDRESS = 'fullAddress';
    public const string FIELD_FULL_ADDRESS_ABBREVIATED = 'fullAddressAbbreviated';

    public const array FILTERABLE_FIELDS = [
        self::FIELD_ID,
        self::FIELD_COUNTRY_CODE,
        self::FIELD_BUILDING_ID,
        self::FIELD_IMPORTED_AT,
    ];
    public const array SEARCHABLE_FIELDS = [
        self::FIELD_FULL_ADDRESS,
        self::FIELD_FULL_ADDRESS_ABBREVIATED,
    ];

    public const array HYDRATION_FIELDS = [
        self::FIELD_JSON_MODEL,
    ];

    public const array SEARCHABLE_HIGHLIGHTED_FIELDS = [
        self::FIELD_FULL_ADDRESS,
        self::FIELD_FULL_ADDRESS_ABBREVIATED,
    ];

    private const string FIELD_RANKING_SCORE = '_rankingScore';
    private const string FIELD_RANKING_SCORE_DETAILS = '_rankingScoreDetails';

    private function __construct() {}

    /**
     * @return array<string, string|int>
     *
     * @throws \JsonException
     */
    public static function buildingAddressAsArray(BuildingAddress $buildingAddress): array
    {
        return [
            self::FIELD_ID => $buildingAddress->id,
            self::FIELD_COUNTRY_CODE => $buildingAddress->address->countryCode,
            self::FIELD_BUILDING_ID => $buildingAddress->buildingId,
            self::FIELD_FULL_ADDRESS => $buildingAddress->address->formatForSearch(),
            self::FIELD_FULL_ADDRESS_ABBREVIATED => $buildingAddress->address->formatForSearch(false),
            self::FIELD_JSON_MODEL => json_encode($buildingAddress, \JSON_THROW_ON_ERROR),
            self::FIELD_IMPORTED_AT => $buildingAddress->importedAt,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function hydrateBuildingAddress(array $result): BuildingAddress
    {
        return BuildingAddress::fromArray(json_decode(
            $result[self::FIELD_JSON_MODEL],
            true,
            512,
            \JSON_THROW_ON_ERROR,
        ));
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return int<0, 100>
     */
    public static function extractScore(array $result): int
    {
        $score = (int) floor(round($result[self::FIELD_RANKING_SCORE], 3) * 100);

        return min(max(0, $score), 100);
    }

    /**
     * @param array<string, mixed> $result
     *
     * @throws \JsonException
     */
    public static function hydrateScoredBuildingAddress(array $result): BuildingAddressScored
    {
        $jsonModel = $result[self::FIELD_JSON_MODEL] ?? null;
        if (null === $jsonModel) {
            throw new \RuntimeException('Invalid data in meilisearch, missing JsonModel data');
        }

        $rankingScoreDetails = null;
        if (\array_key_exists(self::FIELD_RANKING_SCORE_DETAILS, $result)) {
            $rankingScoreDetails = [];
            $rankingScoreDetailsModel = RankingScoreDetails::fromArray($result[self::FIELD_RANKING_SCORE_DETAILS]);

            foreach ($rankingScoreDetailsModel->details as $rankingScoreRuleDetails) {
                $rankingScoreDetails[] = (string) $rankingScoreRuleDetails;
            }
        }

        try {
            $buildingAddress = BuildingAddress::fromArray(json_decode($result[self::FIELD_JSON_MODEL], true, 512, \JSON_THROW_ON_ERROR));
        } catch (\UnexpectedValueException $e) {
            throw new \UnexpectedValueException("Could not decode model from index: {$e->getMessage()} (" . var_export($result, true) . ')', 0, $e);
        }

        return new BuildingAddressScored(
            score: self::extractScore($result),
            matchingHighlight: $result['_formatted'][self::FIELD_FULL_ADDRESS] ?? '',
            buildingAddress: $buildingAddress,
            rankingScoreDetails: $rankingScoreDetails,
        );
    }
}
