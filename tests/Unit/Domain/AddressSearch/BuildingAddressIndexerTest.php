<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch;

use App\Domain\AddressSearch\BuildingAddressIndexer;
use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\AddressSearch\Contract\BuildingAddressBridgedFactoryInterface;
use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\Address;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\PostGis\Coordinates;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[Small]
final class BuildingAddressIndexerTest extends TestCase
{
    private const string UUID1 = '065d7740-526a-7c72-8000-f7532a680456';
    private MockObject&BuildingAddressBridgedFactoryInterface $buildingAddressRepository;
    private MockObject&AddressSearchWriteRepositoryInterface $addressRepository;
    private BuildingAddressIndexer $indexer;

    protected function setUp(): void
    {
        $this->buildingAddressRepository = $this->createMock(BuildingAddressBridgedFactoryInterface::class);
        $this->addressRepository = $this->createMock(AddressSearchWriteRepositoryInterface::class);

        $this->indexer = new BuildingAddressIndexer(
            $this->buildingAddressRepository,
            $this->addressRepository,
        );
    }

    public function testIndexWithNoMatchingEntries(): void
    {
        $id = new Uuid(self::UUID1);

        $this->buildingAddressRepository->expects($this->once())
            ->method('getBuildingAddress')
            ->willThrowException(new BuildingAddressNotFoundException($id))
        ;

        $this->addressRepository->expects($this->never())
            ->method('indexBuildingAddresses')
        ;

        $this->expectException(BuildingAddressNotFoundException::class);
        $this->indexer->indexBuildingAddress($id);
    }

    public function testIndex(): void
    {
        $id = new Uuid(self::UUID1);
        $address = new Address(
            'Limmatstrasse',
            'Limmatstr',
            '183',
            '8005',
            'Zürich',
            'ZH',
            '3',
        );

        $this->buildingAddressRepository->expects($this->once())
            ->method('getBuildingAddress')
            ->willReturn(new BuildingAddress(
                id: self::UUID1,
                buildingId: '111',
                addressId: '222',
                entranceId: '0',
                streetId: '7',
                language: 'de',
                address: $address,
                coordinates: new Coordinates(latitude: '1', longitude: '2'),
                importedAtTimestamp: 0,
            ))
        ;

        $this->addressRepository->expects($this->once())
            ->method('indexBuildingAddresses')
            ->willReturnCallback(function (iterable $items): iterable {
                $this->assertCount(1, $items);

                return $items;
            })
        ;

        $this->indexer->indexBuildingAddress($id);
    }
}
