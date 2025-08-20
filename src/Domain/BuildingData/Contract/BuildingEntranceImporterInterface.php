<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Infrastructure\Model\CountryCodeEnum;

interface BuildingEntranceImporterInterface
{
    /**
     * Returns the amount of building data entries to import.
     */
    public function countBuildingEntrances(?CountryCodeEnum $countryCode = null): int;

    /**
     * Imports all the available building data.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function importBuildingData(?CountryCodeEnum $countryCode = null): iterable;

    /**
     * Imports the given building data (used for testing).
     *
     * @param iterable<BuildingEntranceData> $buildingData
     */
    public function importManualBuildingData(iterable $buildingData): void;
}
