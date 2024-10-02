<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Contract;

use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\BuildingAddress;
use Symfony\Component\Uid\Uuid;

interface BuildingAddressBridgedFactoryInterface
{
    /**
     * Returns the amount of the available building addresses.
     */
    public function countBuildingAddresses(): int;

    /**
     * @return iterable<BuildingAddress>
     */
    public function getBuildingAddresses(): iterable;

    /**
     * @return iterable<BuildingAddress>
     */
    public function getBuildingAddressesImportedSince(\DateTimeImmutable $timestamp): iterable;

    /**
     * @throws BuildingAddressNotFoundException
     */
    public function getBuildingAddress(Uuid $id): BuildingAddress;
}
