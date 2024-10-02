<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch;

use App\Application\Contract\BuildingAddressFinderInterface;
use App\Application\Contract\BuildingAddressStatsProviderInterface;
use App\Domain\AddressSearch\Contract\AddressSearchReadRepositoryInterface;
use App\Infrastructure\Pagination;
use App\Infrastructure\SchemaOrg\Place;
use Symfony\Component\Uid\UuidV7;

final readonly class BuildingAddressFinder implements
    BuildingAddressFinderInterface,
    BuildingAddressStatsProviderInterface
{
    public function __construct(
        private AddressSearchReadRepositoryInterface $addressSearchRepositoryReader,
    ) {}

    public function findPlace(UuidV7 $id): ?Place
    {
        $buildingAddress = $this->addressSearchRepositoryReader->findAddress($id);
        if (null === $buildingAddress) {
            return null;
        }

        return Place::buildFromBuildingAddress($buildingAddress);
    }

    public function countIndexedAddresses(): int
    {
        return $this->addressSearchRepositoryReader->countIndexedAddresses();
    }

    public function findPlaces(Pagination $pagination): iterable
    {
        foreach ($this->addressSearchRepositoryReader->findAddresses($pagination) as $buildingAddress) {
            yield Place::buildFromBuildingAddress($buildingAddress);
        }
    }
}
