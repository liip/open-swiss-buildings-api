<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH;

use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryInterface;
use App\Domain\Registry\DataCH\Model\SwissBuildingStatusEnum;
use App\Domain\Registry\DataCH\Model\SwissLanguageEnum;
use App\Domain\Registry\DataCH\Repository\EntranceRepository;
use App\Domain\Registry\Model\BuildingEntranceData;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;

final readonly class RegistryBuildingDataRepository implements RegistryBuildingDataRepositoryInterface
{
    public function __construct(
        private EntranceRepository $entranceRepository,
    ) {}

    public static function country(): CountryCodeEnum
    {
        return CountryCodeEnum::CH;
    }

    public function countBuildingData(): int
    {
        return $this->entranceRepository->countBuildingData();
    }

    public function getBuildingData(): iterable
    {
        foreach ($this->entranceRepository->getBuildingData() as $row) {
            yield $this->buildModel($row);
        }
    }

    public function getPaginatedBuildingData(Pagination $pagination, BuildingEntranceFilter $filter): iterable
    {
        foreach ($this->entranceRepository->getPaginatedBuildingData($pagination, $filter) as $row) {
            yield $this->buildModel($row);
        }
    }

    /**
     * @param array{
     *       EGID: string,
     *       EDID: string,
     *       EGAID: string,
     *       DEINR: string,
     *       ESID: string,
     *       STRNAME: string,
     *       STRNAMK: string,
     *       STRSP: SwissLanguageEnum,
     *       DPLZ4: string,
     *       DPLZNAME: string,
     *       GGDENR: string,
     *       GGDENAME: string,
     *       GDEKT: string,
     *       DKODE: string,
     *       DKODN: string,
     *       GKODE: string,
     *       GKODN: string,
     *       GSTAT: SwissBuildingStatusEnum,
     *   } $row
     */
    private function buildModel(array $row): BuildingEntranceData
    {
        $coordLV95East = $row['DKODE'];
        $coordLV95North = $row['DKODN'];
        if ('' === $coordLV95East || '' === $coordLV95North) {
            // If the Entrance coordinates are empty, we fallback to the Building coordinates, if available
            $coordLV95East = $row['GKODE'];
            $coordLV95North = $row['GKODN'];
        }

        return new BuildingEntranceData(
            buildingId: $row['EGID'],
            entranceId: $row['EDID'],
            addressId: $row['EGAID'],
            streetHouseNumber: $row['DEINR'],
            streetId: $row['ESID'],
            streetName: $row['STRNAME'],
            streetNameAbbreviation: $row['STRNAMK'],
            streetNameLanguage: $row['STRSP']->toLanguage(),
            countryCode: CountryCodeEnum::CH,
            postalCode: $row['DPLZ4'],
            locality: $row['DPLZNAME'],
            municipality: $row['GGDENAME'],
            municipalityCode: $row['GGDENR'],
            cantonCode: $row['GDEKT'],
            geoCoordinateEastLV95: $coordLV95East,
            geoCoordinateNorthLV95: $coordLV95North,
            buildingStatus: $row['GSTAT']->toBuildingStatus(),
        );
    }
}
