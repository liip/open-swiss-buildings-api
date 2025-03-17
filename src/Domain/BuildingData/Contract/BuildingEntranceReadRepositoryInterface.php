<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Contract;

use App\Domain\BuildingData\Model\BuildingEntrance;
use App\Domain\BuildingData\Model\BuildingEntranceFilter;
use App\Domain\BuildingData\Model\BuildingEntranceStatistics;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use Symfony\Component\Uid\Uuid;

interface BuildingEntranceReadRepositoryInterface
{
    public function getStatistics(CountryCodeEnum $countryCode): BuildingEntranceStatistics;

    /**
     * Returns the amount of the available building entrances, filtered by the given country if given.
     */
    public function countBuildingEntrances(?CountryCodeEnum $countryCode = null): int;

    /**
     * Iterate over the available building entrances, filtered by the given country if given.
     *
     * @return iterable<BuildingEntrance>
     */
    public function getBuildingEntrances(?CountryCodeEnum $countryCode = null): iterable;

    public function findBuildingEntrance(Uuid $id): ?BuildingEntrance;

    /**
     * @return iterable<BuildingEntrance>
     */
    public function getBuildingEntrancesImportedSince(\DateTimeImmutable $timestamp): iterable;

    /**
     * @return iterable<BuildingEntrance>
     */
    public function getPaginatedBuildingEntrances(Pagination $pagination, BuildingEntranceFilter $filter): iterable;

    /**
     * @param positive-int $activeDays
     */
    public function countOutdatedBuildingEntrances(int $activeDays): int;

    /**
     * @param positive-int $activeDays
     *
     * @return iterable<BuildingEntrance>
     */
    public function getOutdatedBuildingEntrances(int $activeDays): iterable;
}
