<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch\Model;

use App\Domain\AddressSearch\Model\BuildingAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type BuildingAddressAsArray from BuildingAddress
 */
#[Small]
final class BuildingAddressTest extends TestCase
{
    /**
     * @return BuildingAddressAsArray
     */
    public static function functionGetEmptyBuildingAddressArray(): array
    {
        return [
            'id' => '000',
            'countryCode' => 'CH',
            'buildingId' => '123',
            'entranceId' => '0',
            'addressId' => '111',
            'streetId' => '7',
            'language' => '',
            'importedAt' => 20250101,
            'address' => [
                'streetName' => '',
                'streetNameAbbreviation' => '',
                'streetHouseNumber' => '',
                'postalCode' => '',
                'locality' => '',
                'municipality' => '',
                'municipalityCode' => '',
                'countryCode' => '',
            ],
            'coordinates' => null,
        ];
    }

    /**
     * @param BuildingAddressAsArray $data
     */
    #[DataProvider('provideCreateFromArrayAndJsonSerializeCases')]
    public function testCreateFromArrayAndJsonSerialize(array $data): void
    {
        $buildingAddress = BuildingAddress::fromArray($data);
        $buildingAddress1 = BuildingAddress::fromArray($buildingAddress->jsonSerialize());

        AddressSearchModelAssert::assertSameBuildingAddress($buildingAddress, $buildingAddress1);
    }

    /**
     * @return iterable<string, array{BuildingAddressAsArray}>
     */
    public static function provideCreateFromArrayAndJsonSerializeCases(): iterable
    {
        yield 'empty' => [self::functionGetEmptyBuildingAddressArray()];
    }
}
