<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch\Model\SchemaOrg;

use App\Infrastructure\PostGis\Coordinates;
use App\Infrastructure\SchemaOrg\Place;
use App\Tests\Util\BuildingAddressModelBuilder;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class PlaceTest extends TestCase
{
    public function testCreateFromBuildingAddress(): void
    {
        $coordinates = ['latitude' => '8.4858953043729', 'longitude' => '47.267684673199'];
        $buildingAddress = BuildingAddressModelBuilder::buildBuildingAddress(
            BuildingAddressModelBuilder::UUID1,
            new \DateTimeImmutable(),
            $coordinates,
        );

        $place = Place::buildFromBuildingAddress($buildingAddress);
        $this->assertSame(BuildingAddressModelBuilder::UUID1, $place->identifier);

        // Address properties
        $this->assertSame(BuildingAddressModelBuilder::LOCALITY, $place->postalAddress->addressLocality);
        $this->assertSame(BuildingAddressModelBuilder::MUNICIPALITY, $place->postalAddress->addressRegion);
        $this->assertSame(BuildingAddressModelBuilder::POSTAL_CODE, $place->postalAddress->postalCode);
        $this->assertSame(
            BuildingAddressModelBuilder::STREET_NAME . ' ' . BuildingAddressModelBuilder::STREET_HOUSE_NUMBER,
            $place->postalAddress->streetAddress,
        );
        $this->assertSame(BuildingAddressModelBuilder::LANGUAGE, $place->postalAddress->inLanguage);

        // Additional properties
        $this->assertSame(BuildingAddressModelBuilder::BUILDING_ID, $place->additionalProperty->buildingId);

        // Geo properties
        $this->assertInstanceOf(Coordinates::class, $place->geo);
        $this->assertSame('47.267684673199', $place->geo->longitude);
        $this->assertSame('8.4858953043729', $place->geo->latitude);
    }

    public function testCreateFromBuildingAddressWithoutCoordinates(): void
    {
        $buildingAddress = BuildingAddressModelBuilder::buildBuildingAddress(BuildingAddressModelBuilder::UUID1);
        $place = Place::buildFromBuildingAddress($buildingAddress);

        // Geo properties
        $this->assertNotInstanceOf(Coordinates::class, $place->geo);
    }
}
