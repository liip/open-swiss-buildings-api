<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Domain\AddressSearch\Model\PlaceScored;

interface BuildingAddressSearcherInterface
{
    /**
     * @return iterable<BuildingAddressScored>
     */
    public function searchBuildingAddress(AddressSearch $search, bool $debug = false): iterable;

    /**
     * @return iterable<PlaceScored>
     */
    public function searchPlaces(AddressSearch $search): iterable;
}
