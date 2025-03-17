<?php

declare(strict_types=1);

namespace App\Infrastructure\SchemaOrg;

use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\PostGis\Coordinates;

final readonly class Place
{
    public function __construct(
        public string $identifier,
        public PostalAddress $postalAddress,
        public ?Coordinates $geo,
        public PlaceAdditionalProperty $additionalProperty,
    ) {}

    public static function buildFromBuildingAddress(BuildingAddress $buildingAddress): self
    {
        return new self(
            identifier: $buildingAddress->id,
            postalAddress: new PostalAddress(
                addressCountry: $buildingAddress->address->countryCode,
                addressLocality: $buildingAddress->address->locality,
                addressRegion: $buildingAddress->address->municipality,
                postalCode: $buildingAddress->address->postalCode,
                streetAddress: $buildingAddress->address->streetName . ' ' . $buildingAddress->address->streetHouseNumber,
                inLanguage: $buildingAddress->language,
            ),
            geo: $buildingAddress->coordinates,
            additionalProperty: new PlaceAdditionalProperty(
                buildingId: $buildingAddress->buildingId,
                entranceId: $buildingAddress->entranceId,
                addressId: $buildingAddress->addressId,
                municipalityCode: $buildingAddress->address->municipalityCode,
            ),
        );
    }
}
