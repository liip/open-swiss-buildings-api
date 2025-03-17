<?php

declare(strict_types=1);

namespace App\Domain\Bridge;

use App\Domain\BuildingData\Contract\BuildingDataBridgedFactoryInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\FederalData\Contract\FederalBuildingDataRepositoryInterface;
use App\Domain\FederalData\Model\BuildingEntranceData as FederalBuildingEntranceData;
use App\Domain\FederalData\Model\EntranceLanguageEnum as FederalEntranceLanguageEnum;
use App\Infrastructure\Address\StreetFactory;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;

final readonly class BuildingDataToFederalDataBridge implements BuildingDataBridgedFactoryInterface
{
    public function __construct(
        private FederalBuildingDataRepositoryInterface $repository,
    ) {}

    public function countBuildingData(): int
    {
        return $this->repository->countBuildingData();
    }

    public function getBuildingData(): iterable
    {
        foreach ($this->repository->getBuildingData() as $buildingData) {
            yield $this->mapBuildingData($buildingData);
        }
    }

    private function mapBuildingData(FederalBuildingEntranceData $federalBuildingEntrance): BuildingEntranceData
    {
        $streetName = $federalBuildingEntrance->streetName;
        if ('' === $streetName) {
            $streetName = null;
        }
        $houseNumber = $federalBuildingEntrance->streetHouseNumber;
        if ('' === $houseNumber) {
            $houseNumber = null;
        }

        $street = null;
        if (null !== $streetName || null !== $houseNumber) {
            $street = StreetFactory::createFromSeparateStrings($streetName, $houseNumber);
        }

        $streetAbbreviated = null;
        if ('' !== $federalBuildingEntrance->streetNameAbbreviation) {
            $streetAbbreviated = StreetFactory::createFromSeparateStrings($federalBuildingEntrance->streetNameAbbreviation, $houseNumber);
        }

        return new BuildingEntranceData(
            countryCode: CountryCodeEnum::CH,
            buildingId: $federalBuildingEntrance->buildingId,
            entranceId: $federalBuildingEntrance->entranceId,
            addressId: $federalBuildingEntrance->addressId,
            streetNameLanguage: $this->mapFederalLanguage($federalBuildingEntrance->streetNameLanguage),
            streetId: $federalBuildingEntrance->streetId,
            street: $street,
            streetAbbreviated: $streetAbbreviated,
            postalCode: $federalBuildingEntrance->postalCode,
            locality: $federalBuildingEntrance->locality,
            municipality: $federalBuildingEntrance->municipality,
            municipalityCode: $federalBuildingEntrance->municipalityCode,
            cantonCode: $federalBuildingEntrance->cantonCode,
            geoCoordinateEastLV95: $federalBuildingEntrance->geoCoordinateEastLV95,
            geoCoordinateNorthLV95: $federalBuildingEntrance->geoCoordinateNorthLV95,
        );
    }

    private function mapFederalLanguage(FederalEntranceLanguageEnum $federalLanguage): LanguageEnum
    {
        return match ($federalLanguage) {
            FederalEntranceLanguageEnum::DE => LanguageEnum::DE,
            FederalEntranceLanguageEnum::FR => LanguageEnum::FR,
            FederalEntranceLanguageEnum::IT => LanguageEnum::IT,
            FederalEntranceLanguageEnum::RM => LanguageEnum::RM,
            FederalEntranceLanguageEnum::UNKNOWN => LanguageEnum::UNKNOWN,
        };
    }
}
