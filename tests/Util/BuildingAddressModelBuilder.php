<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\PostGis\Coordinates;

/**
 * @phpstan-import-type GeoCoordinatesAsArray from Coordinates
 */
final class BuildingAddressModelBuilder
{
    public const string UUID1 = '065d7740-526a-7c72-8000-f7532a680456';

    public const string UUID2 = '065d7741-54ec-7b41-8000-608eba1884b8';

    public const string BUILDING_ID = '1600017';

    public const string ENTRANCE_ID = '1';

    public const string ADDRESS_ID = '100962765';

    public const string LANGUAGE = 'de';

    public const string LOCALITY = 'Aeugstertal';

    public const int IMPORTED_AT = 20250102;

    public const string STREET_NAME = 'Reppischtalstrasse';

    public const string STREET_NAME_ABBREVIATED = 'Reppischtalstr.';

    public const string STREET_HOUSE_NUMBER = '34';

    public const string POSTAL_CODE = '8914';

    public const string MUNICIPALITY = 'ZH';

    public const string MUNICIPALITY_CODE = '3';

    public const string COUNTRY_CODE = 'CH';

    private function __construct() {}

    /**
     * @param non-empty-string           $uuid
     * @param GeoCoordinatesAsArray|null $coordinates
     */
    public static function buildBuildingAddress(
        string $uuid,
        int $importedAt = self::IMPORTED_AT,
        ?array $coordinates = null,
    ): BuildingAddress {
        return BuildingAddress::fromArray([
            'id' => $uuid,
            'countryCode' => self::COUNTRY_CODE,
            'buildingId' => self::BUILDING_ID,
            'entranceId' => self::ENTRANCE_ID,
            'addressId' => self::ADDRESS_ID,
            'language' => self::LANGUAGE,
            'importedAt' => $importedAt,
            'address' => [
                'streetName' => self::STREET_NAME,
                'streetNameAbbreviation' => self::STREET_NAME_ABBREVIATED,
                'streetHouseNumber' => self::STREET_HOUSE_NUMBER,
                'postalCode' => self::POSTAL_CODE,
                'locality' => self::LOCALITY,
                'municipality' => self::MUNICIPALITY,
                'municipalityCode' => self::MUNICIPALITY_CODE,
                'countryCode' => self::COUNTRY_CODE,
            ],
            'coordinates' => $coordinates,
        ]);
    }
}
