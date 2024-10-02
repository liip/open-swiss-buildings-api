<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

use App\Infrastructure\SchemaOrg\Place;

final class PlaceScored
{
    public function __construct(
        public int $score,
        public Place $place,
    ) {}

    public static function buildFromBuildingAddressScored(BuildingAddressScored $buildingAddressScored): self
    {
        return new self(
            score: $buildingAddressScored->score,
            place: Place::buildFromBuildingAddress($buildingAddressScored->buildingAddress),
        );
    }
}
