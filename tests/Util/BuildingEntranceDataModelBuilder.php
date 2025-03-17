<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Model\CountryCodeEnum;

final readonly class BuildingEntranceDataModelBuilder
{
    public const CountryCodeEnum COUNTRY_CODE = CountryCodeEnum::CH;
    public const string BUILDING_ID = '2366055';
    public const string ENTRANCE_ID = '0';
    public const string ZIP_CODE = '8005';
    public const string STREET_ID = '10004212';
    public const string LOCALITY = 'Zürich';
    public const string CANTON_CODE = 'ZH';
    public const int STREET_NUMBER = 183;
    public const string MUNICIPALITY_CODE =  '261';

    public const string COORDINATES_LAT_GWS84 = '47.386170922358';
    public const string COORDINATES_LON_GWS84 = '8.5292387777084';
    public const string COORDINATES_EAST_LV95 = '2682348.561';
    public const string COORDINATES_NORTH_LV95 = '1248943.136';
    public const string STREET_NAME = 'Limmatstrasse';
    public const string STREET_NAME_ABBR = 'Limmatstr';

    /**
     * @param positive-int|null     $streetNumber
     * @param non-empty-string|null $streetNumberSuffix
     */
    public static function createLiipBuildingEntranceData(
        CountryCodeEnum $countryCode = self::COUNTRY_CODE,
        string $entranceId = self::ENTRANCE_ID,
        string $buildingId = self::BUILDING_ID,
        string $streetId = self::STREET_ID,
        string $postalCode = self::ZIP_CODE,
        ?int $streetNumber = self::STREET_NUMBER,
        ?string $streetNumberSuffix = null,
    ): BuildingEntranceData {
        $streetNr = null;
        if (null !== $streetNumber || null !== $streetNumberSuffix) {
            $streetNr = new StreetNumber($streetNumber, $streetNumberSuffix);
        }

        return BuildingEntranceData::create(
            countryCode: $countryCode,
            buildingId: $buildingId,
            entranceId: $entranceId,
            streetId: $streetId,
            street: new Street('' . self::STREET_NAME, $streetNr),
            streetAbbreviated: new Street(self::STREET_NAME_ABBR, $streetNr),
            postalCode: $postalCode,
            locality: self::LOCALITY,
            municipalityCode: self::MUNICIPALITY_CODE,
            cantonCode: self::CANTON_CODE,
            geoCoordinateEastLV95: self::COORDINATES_EAST_LV95,
            geoCoordinateNorthLV95: self::COORDINATES_NORTH_LV95,
        );
    }

    /**
     * @param array<string, string> $defaultOverride
     *
     * @return array<string, string>
     */
    public static function createLiipBuildingResult(array $defaultOverride): array
    {
        return array_merge([
            'country_code' => self::COUNTRY_CODE->value,
            'egid' => self::BUILDING_ID,
            'edid' => self::ENTRANCE_ID,
            'municipality_code' => self::MUNICIPALITY_CODE,
            'postal_code' => self::ZIP_CODE,
            'locality' => self::LOCALITY,
            'street_name' => self::STREET_NAME,
            'street_house_number' => (string) self::STREET_NUMBER,
            'latitude' => self::COORDINATES_LAT_GWS84,
            'longitude' => self::COORDINATES_LON_GWS84,
        ], $defaultOverride);
    }

    /**
     * @param array<string, string> $defaultOverride
     *
     * @return array<string, string>
     */
    public static function createEmptyBuildingResult(array $defaultOverride): array
    {
        return array_merge([
            'country_code' => '',
            'egid' => '',
            'edid' => '',
            'municipality_code' => '',
            'postal_code' => '',
            'locality' => '',
            'street_name' => '',
            'street_house_number' => '',
            'latitude' => '',
            'longitude' => '',
        ], $defaultOverride);
    }
}
