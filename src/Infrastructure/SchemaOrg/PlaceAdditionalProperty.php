<?php

declare(strict_types=1);

namespace App\Infrastructure\SchemaOrg;

final readonly class PlaceAdditionalProperty
{
    public function __construct(
        /**
         * The BuildingID (EGID) of the item.
         */
        public string $buildingId,

        /**
         * The EntranceID (EDID) of the item.
         */
        public string $entranceId,

        /**
         * The AddressID (EGAID) of the item.
         */
        public string $addressId,

        /**
         * The Municipality code of the item.
         */
        public string $municipalityCode,
    ) {}
}
