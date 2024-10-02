<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

use App\Infrastructure\Address\Model\Street;

final readonly class BuildingEntranceData
{
    public function __construct(
        /**
         * Federal Building identifier.
         */
        public string $buildingId,

        /**
         * Federal Entrance identifier.
         */
        public string $entranceId,

        /**
         * Federal Building-Address identifier.
         */
        public string $addressId,
        public EntranceLanguageEnum $streetNameLanguage,
        public string $streetId,
        public ?Street $street,
        public ?Street $streetAbbreviated,
        public string $postalCode,
        public string $locality,
        public string $municipality,
        public string $municipalityCode,

        /**
         * Canton code.
         *
         * @example "GR"
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
    ) {}

    public static function create(
        string $buildingId,
        string $entranceId,
        string $streetId,
        ?Street $street,
        ?Street $streetAbbreviated,
        string $postalCode,
        string $locality,
        string $municipalityCode,
        string $cantonCode,
        string $geoCoordinateEastLV95,
        string $geoCoordinateNorthLV95,
        string $municipality = '',
        ?string $addressId = null,
        EntranceLanguageEnum $streetNameLanguage = EntranceLanguageEnum::DE,
    ): self {
        return new self(
            $buildingId,
            $entranceId,
            $addressId ?? "{$buildingId}-{$entranceId}",
            $streetNameLanguage,
            $streetId,
            $street,
            $streetAbbreviated,
            $postalCode,
            $locality,
            $municipality,
            $municipalityCode,
            $cantonCode,
            $geoCoordinateEastLV95,
            $geoCoordinateNorthLV95,
        );
    }
}
