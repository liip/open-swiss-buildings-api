<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Model;

final readonly class BuildingEntranceData
{
    public function __construct(
        /**
         * Federal Building identifier (numeric, 9chars).
         */
        public string $buildingId,

        /**
         * Federal Entrance identifier (numeric, 2chars).
         */
        public string $entranceId,

        /**
         * Federal Building-Address identifier (numeric, 9chars).
         */
        public string $addressId,

        /**
         * House number (alpha-numeric, 12chars).
         */
        public string $streetHouseNumber,

        /**
         * Street ID.
         */
        public string $streetId,

        /**
         * Street name (alpha-numeric, 60chars).
         */
        public string $streetName,

        /**
         * Street name, abbreviation (alpha-numeric, 24chars).
         */
        public string $streetNameAbbreviation,

        /**
         * Street name language.
         */
        public EntranceLanguageEnum $streetNameLanguage,

        /**
         * Postal code (numeric, 4chars).
         */
        public string $postalCode,

        /**
         * Location name (alpha-numeric, 40chars).
         */
        public string $locality,

        /**
         * Municipality (alpha, 40chars).
         */
        public string $municipality,

        /**
         * Municipality code (numeric, 4chars).
         */
        public string $municipalityCode,

        /**
         * Canton code (alpha, 2chars).
         *
         * @example "GR"
         */
        public string $cantonCode,

        /**
         * LV95 Coordinates: East (numeric, 11 chars).
         */
        public string $geoCoordinateEastLV95,

        /**
         * LV95 Coordinates: North (numeric, 11 chars).
         */
        public string $geoCoordinateNorthLV95,

        /**
         * Status of the building.
         */
        public BuildingStatusEnum $buildingStatus,
    ) {}
}
