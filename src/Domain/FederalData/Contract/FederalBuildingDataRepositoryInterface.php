<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Contract;

use App\Domain\FederalData\Model\BuildingEntranceData;
use App\Domain\FederalData\Model\FederalEntranceFilter;
use App\Infrastructure\Pagination;

interface FederalBuildingDataRepositoryInterface
{
    /**
     * Returns the amount of building data entries available.
     *
     * This excludes data about irrelevant buildings for this application.
     */
    public function countBuildingData(): int;

    /**
     * Returns a list of available building data.
     *
     * This excludes data about irrelevant buildings for this application.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function getBuildingData(): iterable;

    /**
     * Returns a limited set of the available building data.
     *
     * @return iterable<BuildingEntranceData>
     */
    public function getPaginatedBuildingData(Pagination $pagination, FederalEntranceFilter $filter): iterable;
}
