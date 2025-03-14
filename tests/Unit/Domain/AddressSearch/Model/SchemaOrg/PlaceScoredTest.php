<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch\Model\SchemaOrg;

use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Domain\AddressSearch\Model\PlaceScored;
use App\Infrastructure\PostGis\Coordinates;
use App\Tests\Util\BuildingAddressModelBuilder;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class PlaceScoredTest extends TestCase
{
    public function testCreateFromBuildingAddressScored(): void
    {
        $coordinates = ['latitude' => '8.4858953043729', 'longitude' => '47.267684673199'];
        $buildingAddress = BuildingAddressModelBuilder::buildBuildingAddress(
            BuildingAddressModelBuilder::UUID1,
            new \DateTimeImmutable(),
            $coordinates,
        );
        $buildingAddressScored = new BuildingAddressScored(
            30,
            '',
            $buildingAddress,
        );

        $placeScored = PlaceScored::buildFromBuildingAddressScored($buildingAddressScored);

        $this->assertSame(30, $placeScored->score);

        $place = $placeScored->place;
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
}
