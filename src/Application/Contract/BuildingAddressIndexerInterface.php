<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\BuildingAddress;
use Symfony\Component\Uid\Uuid;

interface BuildingAddressIndexerInterface
{
    /**
     * Returns the amount of building addresses available to index.
     */
    public function countBuildingAddresses(): int;

    /**
     * Indexes all building addresses for search.
     *
     * @return iterable<BuildingAddress>
     */
    public function indexBuildingAddresses(): iterable;

    /**
     * Indexes building addresses for search, which were imported since the given timestamp.
     *
     * @return iterable<BuildingAddress>
     */
    public function indexBuildingAddressesImportedSince(\DateTimeImmutable $timestamp): iterable;

    /**
     * Indexes a single building address for search.
     *
     * @throws BuildingAddressNotFoundException
     */
    public function indexBuildingAddress(Uuid $id): void;
}
