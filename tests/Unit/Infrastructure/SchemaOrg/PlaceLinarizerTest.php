<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\SchemaOrg;

use App\Infrastructure\SchemaOrg\Place;
use App\Infrastructure\SchemaOrg\PlaceLinearizer;
use App\Tests\Util\BuildingAddressModelBuilder;
use PHPUnit\Framework\TestCase;

final class PlaceLinarizerTest extends TestCase
{
    public function testHeaders(): void
    {
        $place = Place::buildFromBuildingAddress(BuildingAddressModelBuilder::buildBuildingAddress(
            BuildingAddressModelBuilder::UUID1,
            BuildingAddressModelBuilder::IMPORTED_AT,
            ['latitude' => '47.269117135498', 'longitude' => '8.4490957266308'],
        ));

        $this->assertSame(PlaceLinearizer::headers(), array_keys(PlaceLinearizer::linearized($place)));
    }

    public function testLinearize(): void
    {
        $place = Place::buildFromBuildingAddress(BuildingAddressModelBuilder::buildBuildingAddress(
            BuildingAddressModelBuilder::UUID1,
            BuildingAddressModelBuilder::IMPORTED_AT,
            ['latitude' => '47.269117135498', 'longitude' => '8.4490957266308'],
        ));

        $linearized = PlaceLinearizer::linearized($place);

        $this->assertSame(BuildingAddressModelBuilder::UUID1, $linearized['identifier']);
        $this->assertSame(BuildingAddressModelBuilder::LOCALITY, $linearized['postalAddress.addressLocality']);
        $this->assertSame(BuildingAddressModelBuilder::MUNICIPALITY, $linearized['postalAddress.addressRegion']);
        $this->assertSame(BuildingAddressModelBuilder::POSTAL_CODE, $linearized['postalAddress.postalCode']);
        $this->assertSame(BuildingAddressModelBuilder::STREET_NAME . ' ' . BuildingAddressModelBuilder::STREET_HOUSE_NUMBER, $linearized['postalAddress.streetAddress']);
        $this->assertSame(BuildingAddressModelBuilder::LANGUAGE, $linearized['postalAddress.inLanguage']);
        $this->assertSame(BuildingAddressModelBuilder::BUILDING_ID, $linearized['additionalProperty.buildingId']);
        $this->assertSame(BuildingAddressModelBuilder::ENTRANCE_ID, $linearized['additionalProperty.entranceId']);
        $this->assertSame(BuildingAddressModelBuilder::ADDRESS_ID, $linearized['additionalProperty.addressId']);
        $this->assertSame(BuildingAddressModelBuilder::MUNICIPALITY_CODE, $linearized['additionalProperty.municipalityCode']);
        $this->assertSame('47.269117135498', $linearized['geo.latitude']);
        $this->assertSame('8.4490957266308', $linearized['geo.longitude']);
    }
}
