<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Contract;

use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Uid\Uuid;

interface BuildingAddressBridgedFactoryInterface
{
    /**
     * Returns the amount of the available building entrances, filtered by the given country if provided.
     */
    public function countBuildingEntrances(?CountryCodeEnum $countryCode = null): int;

    /**
     * Iterate over the building addresses, built from the BuildingEntrances, filtered by the given country if provided.
     *
     * @return iterable<BuildingAddress>
     */
    public function getBuildingAddresses(?CountryCodeEnum $countryCode = null): iterable;

    /**
     * @return iterable<BuildingAddress>
     */
    public function getBuildingAddressesImportedSince(\DateTimeImmutable $timestamp): iterable;

    /**
     * @throws BuildingAddressNotFoundException
     */
    public function getBuildingAddress(Uuid $id): BuildingAddress;
}
