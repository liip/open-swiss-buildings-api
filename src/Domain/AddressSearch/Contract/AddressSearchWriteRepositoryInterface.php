<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Contract;

use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\Model\CountryCodeEnum;

interface AddressSearchWriteRepositoryInterface
{
    /**
     * @param iterable<BuildingAddress> $buildingAddresses
     *
     * @return iterable<BuildingAddress>
     */
    public function indexBuildingAddresses(iterable $buildingAddresses): iterable;

    public function deleteByImportedAtBefore(\DateTimeImmutable $dateTime, ?CountryCodeEnum $countryCode = null): void;

    /**
     * @param list<string> $ids
     */
    public function deleteByIds(array $ids): void;
}
