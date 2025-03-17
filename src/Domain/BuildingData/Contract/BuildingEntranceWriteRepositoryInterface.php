<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Infrastructure\Model\CountryCodeEnum;

interface BuildingEntranceWriteRepositoryInterface
{
    /**
     * Stores all the given building entrances.
     *
     * @param iterable<BuildingEntranceData> $buildingEntrances
     *
     * @return iterable<BuildingEntranceData>
     */
    public function store(iterable $buildingEntrances): iterable;

    /**
     * Deletes all outdated building entrances and returns the amount of deleted entries.
     *
     * @param positive-int $activeDays
     */
    public function deleteOutdatedBuildingEntrances(int $activeDays, ?CountryCodeEnum $countryCode = null): int;
}
