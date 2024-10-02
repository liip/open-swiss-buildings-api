<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Contract\AddressSearchReadRepositoryInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Domain\AddressSearch\Model\PlaceScored;

final readonly class BuildingAddressSearcher implements BuildingAddressSearcherInterface
{
    public function __construct(
        private AddressSearchReadRepositoryInterface $addressSearchRepositoryReader,
    ) {}

    public function searchBuildingAddress(AddressSearch $search, bool $debug = false): iterable
    {
        return $this->addressSearchRepositoryReader->searchAddress($search, $debug);
    }

    public function searchPlaces(AddressSearch $search): iterable
    {
        foreach ($this->searchBuildingAddress($search) as $buildingAddressScored) {
            /* @var BuildingAddressScored $buildingAddressScored */
            yield PlaceScored::buildFromBuildingAddressScored($buildingAddressScored);
        }
    }
}
