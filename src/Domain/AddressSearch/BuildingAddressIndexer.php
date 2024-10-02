<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch;

use App\Application\Contract\BuildingAddressIndexerInterface;
use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\AddressSearch\Contract\BuildingAddressBridgedFactoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class BuildingAddressIndexer implements BuildingAddressIndexerInterface
{
    public function __construct(
        private BuildingAddressBridgedFactoryInterface $buildingAddressRepository,
        private AddressSearchWriteRepositoryInterface $addressRepository,
    ) {}

    public function countBuildingAddresses(): int
    {
        return $this->buildingAddressRepository->countBuildingAddresses();
    }

    public function indexBuildingAddresses(): iterable
    {
        $buildingAddresses = $this->buildingAddressRepository->getBuildingAddresses();
        yield from $this->addressRepository->indexBuildingAddresses($buildingAddresses);
    }

    public function indexBuildingAddressesImportedSince(\DateTimeImmutable $timestamp): iterable
    {
        $buildingAddresses = $this->buildingAddressRepository->getBuildingAddressesImportedSince($timestamp);
        yield from $this->addressRepository->indexBuildingAddresses($buildingAddresses);
    }

    public function indexBuildingAddress(Uuid $id): void
    {
        $buildingAddress = $this->buildingAddressRepository->getBuildingAddress($id);

        foreach ($this->addressRepository->indexBuildingAddresses([$buildingAddress]) as $result) {
            // Loop for indexing to happen
        }
    }
}
