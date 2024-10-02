<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Contract;

use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Infrastructure\Pagination;
use Symfony\Component\Uid\UuidV7;

interface AddressSearchReadRepositoryInterface
{
    /**
     * @return iterable<BuildingAddressScored>
     */
    public function searchAddress(AddressSearch $addressSearch, bool $debug = false): iterable;

    /**
     * Tries to find the address with the given ID.
     */
    public function findAddress(UuidV7 $id): ?BuildingAddress;

    /**
     * List the addresses in the index, matching the given filter.
     *
     * @return iterable<BuildingAddress>
     */
    public function findAddresses(Pagination $pagination): iterable;

    public function countIndexedAddresses(): int;
}
