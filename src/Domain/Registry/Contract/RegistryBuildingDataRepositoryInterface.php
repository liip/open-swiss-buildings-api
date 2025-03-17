<?php

declare(strict_types=1);

namespace App\Domain\Registry\Contract;

use App\Domain\Registry\Model\BuildingEntranceData;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(RegistryBuildingDataRepositoryInterface::class)]
interface RegistryBuildingDataRepositoryInterface
{
    /**
     * Identify the country this registry provides.
     */
    public static function country(): CountryCodeEnum;

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
    public function getPaginatedBuildingData(Pagination $pagination, BuildingEntranceFilter $filter): iterable;
}
