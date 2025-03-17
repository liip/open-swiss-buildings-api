<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch\Model;

use App\Domain\AddressSearch\Model\Address;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\PostGis\Coordinates;
use PHPUnit\Framework\TestCase;

final class AddressSearchModelAssert
{
    private function __construct() {}

    public static function assertSameBuildingAddress(BuildingAddress $expected, BuildingAddress $actual): void
    {
        TestCase::assertSame($expected->id, $actual->id);
        TestCase::assertSame($expected->buildingId, $actual->buildingId);
        TestCase::assertSame($expected->entranceId, $actual->entranceId);
        TestCase::assertSame($expected->addressId, $actual->addressId);
        TestCase::assertSame($expected->language, $actual->language);
        TestCase::assertSame($expected->importedAt, $actual->importedAt);

        self::assertSameAddress($expected->address, $actual->address);

        if (null === $expected->coordinates) {
            TestCase::assertNull($actual->coordinates);
        } else {
            TestCase::assertNotNull($actual->coordinates);
            self::assertSameGeoCoordinates($expected->coordinates, $actual->coordinates);
        }
    }

    public static function assertSameAddress(Address $expected, Address $actual): void
    {
        TestCase::assertSame($expected->countryCode, $actual->countryCode);
        TestCase::assertSame($expected->streetName, $actual->streetName);
        TestCase::assertSame($expected->streetNameAbbreviation, $actual->streetNameAbbreviation);
        TestCase::assertSame($expected->streetHouseNumber, $actual->streetHouseNumber);
        TestCase::assertSame($expected->postalCode, $actual->postalCode);
        TestCase::assertSame($expected->locality, $actual->locality);
        TestCase::assertSame($expected->municipality, $actual->municipality);
    }

    public static function assertSameGeoCoordinates(Coordinates $expected, Coordinates $actual): void
    {
        TestCase::assertSame($expected->latitude, $actual->latitude);
        TestCase::assertSame($expected->longitude, $actual->longitude);
    }
}
