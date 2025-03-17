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
     * Returns the amount of the available building entrances.
     */
    public function countBuildingEntrances(): int;

    /**
     * @return iterable<BuildingEntrance>
     */
    public function getBuildingEntrances(): iterable;

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
