<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch\Model;

use App\Domain\AddressSearch\Model\Address;
use App\Tests\Util\BuildingAddressModelBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type AddressAsArray from Address
 */
#[Small]
#[CoversClass(Address::class)]
final class AddressTest extends TestCase
{
    /**
     * @return AddressAsArray
     */
    public static function getAddressAsArrayEmpty(): array
    {
        return [
            'streetName' => '',
            'streetNameAbbreviation' => '',
            'streetHouseNumber' => '',
            'postalCode' => '',
            'locality' => '',
            'municipality' => '',
            'municipalityCode' => '',
            'countryCode' => '',
        ];
    }

    /**
     * @return AddressAsArray
     */
    public static function getAddressAsArrayWithValues(): array
    {
        return [
            'streetName' => BuildingAddressModelBuilder::STREET_NAME,
            'streetNameAbbreviation' => BuildingAddressModelBuilder::STREET_NAME_ABBREVIATED,
            'streetHouseNumber' => BuildingAddressModelBuilder::STREET_HOUSE_NUMBER,
            'postalCode' => BuildingAddressModelBuilder::POSTAL_CODE,
            'locality' => BuildingAddressModelBuilder::LOCALITY,
            'municipality' => BuildingAddressModelBuilder::MUNICIPALITY,
            'municipalityCode' => BuildingAddressModelBuilder::MUNICIPALITY_CODE,
            'countryCode' => BuildingAddressModelBuilder::COUNTRY_CODE,
        ];
    }

    /**
     * @return iterable<array<AddressAsArray>>
     */
    public static function createFromArrayDataProvider(): iterable
    {
        yield 'empty' => [self::getAddressAsArrayEmpty()];

        $data = self::getAddressAsArrayWithValues();
        yield 'values' => [$data];
    }

    /**
     * @param AddressAsArray $data
     */
    #[DataProvider('createFromArrayDataProvider')]
    public function testCreateFromArrayAndJsonSerialize(array $data): void
    {
        $buildingAddress = Address::fromArray($data);
        $buildingAddress1 = Address::fromArray($buildingAddress->jsonSerialize());

        AddressSearchModelAssert::assertSameAddress($buildingAddress, $buildingAddress1);
    }

    /**
     * @return iterable<array{Address, string, string}>
     */
    public static function createFormatForSearchDataProvider(): iterable
    {
        yield 'empty' => [Address::fromArray(self::getAddressAsArrayEmpty()), '', ''];

        $data = self::getAddressAsArrayWithValues();
        yield 'values' => [Address::fromArray($data), 'Reppischtalstrasse 34 8914 Aeugstertal', 'Reppischtalstr. 34 8914 Aeugstertal'];
    }

    #[DataProvider('createFormatForSearchDataProvider')]
    public function testFormatForSearch(Address $address, string $formatFull, string $formatAbbr): void
    {
        $this->assertSame($formatFull, $address->formatForSearch());
        $this->assertSame($formatAbbr, $address->formatForSearch(false));
    }
}
