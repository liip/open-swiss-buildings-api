<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch;

use App\Application\Contract\BuildingAddressIndexerInterface;
use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\AddressSearch\Contract\BuildingAddressBridgedFactoryInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class BuildingAddressIndexer implements BuildingAddressIndexerInterface
{
    public function __construct(
        private BuildingAddressBridgedFactoryInterface $buildingAddressRepository,
        private AddressSearchWriteRepositoryInterface $addressRepository,
    ) {}

    public function count(?CountryCodeEnum $countryCode = null): int
    {
        return $this->buildingAddressRepository->countBuildingEntrances($countryCode);
    }

    public function indexAll(?CountryCodeEnum $countryCode = null): iterable
    {
        $buildingAddresses = $this->buildingAddressRepository->getBuildingAddresses($countryCode);
        yield from $this->addressRepository->indexBuildingAddresses($buildingAddresses);
    }

    public function indexImportedSince(\DateTimeImmutable $timestamp): iterable
    {
        $buildingAddresses = $this->buildingAddressRepository->getBuildingAddressesImportedSince($timestamp);
        yield from $this->addressRepository->indexBuildingAddresses($buildingAddresses);
    }

    public function indexById(Uuid $id): void
    {
        $buildingAddress = $this->buildingAddressRepository->getBuildingAddress($id);

        foreach ($this->addressRepository->indexBuildingAddresses([$buildingAddress]) as $result) {
            // Loop for indexing to happen
        }
    }
}
