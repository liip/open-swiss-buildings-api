<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntranceData;

interface BuildingDataBridgedFactoryInterface
{
    /**
     * Returns the amount of building data entries available.
     */
    public function countBuildingData(): int;

    /**
     * Returns a list of available building data.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function getBuildingData(): iterable;
}
