<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Contract;

use App\Domain\AddressSearch\Model\BuildingAddress;

interface AddressSearchWriteRepositoryInterface
{
    /**
     * @param iterable<BuildingAddress> $buildingAddresses
     *
     * @return iterable<BuildingAddress>
     */
    public function indexBuildingAddresses(iterable $buildingAddresses): iterable;

    public function deleteByImportedAtBefore(\DateTimeImmutable $dateTime): void;

    /**
     * @param list<string> $ids
     */
    public function deleteByIds(array $ids): void;
}
