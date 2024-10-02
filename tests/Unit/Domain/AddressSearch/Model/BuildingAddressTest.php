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
            'buildingId' => '123',
            'entranceId' => '0',
            'addressId' => '111',
            'streetId' => '7',
            'language' => '',
            'importedAtTimestamp' => 0,
            'address' => [
                'streetName' => '',
                'streetNameAbbreviation' => '',
                'streetHouseNumber' => '',
                'postalCode' => '',
                'locality' => '',
                'municipality' => '',
                'municipalityCode' => '',
            ],
            'coordinates' => null,
        ];
    }

    /**
     * @return iterable<string, array{BuildingAddressAsArray}>
     */
    public static function createFromArrayDataProvider(): iterable
    {
        yield 'empty' => [self::functionGetEmptyBuildingAddressArray()];
    }

    /**
     * @param BuildingAddressAsArray $data
     */
    #[DataProvider('createFromArrayDataProvider')]
    public function testCreateFromArrayAndJsonSerialize(array $data): void
    {
        $buildingAddress = BuildingAddress::fromArray($data);
        $buildingAddress1 = BuildingAddress::fromArray($buildingAddress->jsonSerialize());

        AddressSearchModelAssert::assertSameBuildingAddress($buildingAddress, $buildingAddress1);
    }
}
