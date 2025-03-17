<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Infrastructure\Model\CountryCodeEnum;

interface BuildingDataBridgedFactoryInterface
{
    /**
     * Returns the amount of building data entries available.
     *
     * Filter by the given country, if provided.
     */
    public function countBuildingData(?CountryCodeEnum $country = null): int;

    /**
     * Returns a list of available building data.
     *
     * Filter by the given country, if provided.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function getBuildingData(?CountryCodeEnum $country = null): iterable;
}
