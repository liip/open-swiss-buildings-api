<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntranceData;

interface BuildingEntranceImporterInterface
{
    /**
     * Returns the amount of building data entries to import.
     */
    public function countBuildingEntrances(): int;

    /**
     * Imports all the available building data.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function importBuildingData(): iterable;

    /**
     * Imports the given building data.
     *
     * @param iterable<BuildingEntranceData> $buildingData
     */
    public function importManualBuildingData(iterable $buildingData): void;
}
