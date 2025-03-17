<?php

declare(strict_types=1);

namespace App\Domain\Registry\Model;

use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;

final readonly class BuildingEntranceData
{
    public function __construct(
        /**
         * Building identifier.
         */
        public string $buildingId,

        /**
         * Entrance identifier.
         */
        public string $entranceId,

        /**
         * Building-Address identifier.
         */
        public string $addressId,

        /**
         * House number.
         */
        public string $streetHouseNumber,

        /**
         * Street ID.
         */
        public string $streetId,

        /**
         * Street name.
         */
        public string $streetName,

        /**
         * Street name, abbreviation.
         */
        public string $streetNameAbbreviation,

        /**
         * Street name language.
         */
        public LanguageEnum $streetNameLanguage,

        /**
         * Country of the building entrance.
         */
        public CountryCodeEnum $countryCode,

        /**
         * Postal code.
         */
        public string $postalCode,

        /**
         * Location name.
         */
        public string $locality,

        /**
         * Municipality.
         */
        public string $municipality,

        /**
         * Municipality code.
         */
        public string $municipalityCode,

        /**
         * Canton code.
         */
        public string $cantonCode,

        /**
         * LV95 Coordinates: East.
         */
        public string $geoCoordinateEastLV95,

        /**
         * LV95 Coordinates: North.
         */
        public string $geoCoordinateNorthLV95,

        /**
         * Status of the building.
         */
        public BuildingStatusEnum $buildingStatus,
    ) {}
}
