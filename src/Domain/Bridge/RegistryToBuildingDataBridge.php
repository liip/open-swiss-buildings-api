<?php

declare(strict_types=1);

namespace App\Domain\Bridge;

use App\Domain\BuildingData\Contract\BuildingDataBridgedFactoryInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryProviderInterface;
use App\Domain\Registry\Model\BuildingEntranceData as RegistryBuildingEntranceData;
use App\Infrastructure\Address\StreetFactory;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;

final readonly class RegistryToBuildingDataBridge implements BuildingDataBridgedFactoryInterface
{
    public function __construct(
        private RegistryBuildingDataRepositoryProviderInterface $repositoryProvider,
    ) {}

    public function countBuildingData(?CountryCodeEnum $country = null): int
    {
        if ($country instanceof CountryCodeEnum) {
            return $this->repositoryProvider->getRepository($country)->countBuildingData();
        }

        $count = 0;
        foreach ($this->repositoryProvider->getAllRepositories() as $repository) {
            $count += $repository->countBuildingData();
        }

        return $count;
    }

    public function getBuildingData(?CountryCodeEnum $country = null): iterable
    {
        if ($country instanceof CountryCodeEnum) {
            foreach ($this->repositoryProvider->getRepository($country)->getBuildingData() as $buildingData) {
                yield $this->mapBuildingData($buildingData);
            }

            return;
        }

        foreach ($this->repositoryProvider->getAllRepositories() as $repository) {
            foreach ($repository->getBuildingData() as $buildingData) {
                yield $this->mapBuildingData($buildingData);
            }
        }
    }

    private function mapBuildingData(RegistryBuildingEntranceData $registryBuildingEntrance): BuildingEntranceData
    {
        $streetName = $registryBuildingEntrance->streetName;
        if ('' === $streetName) {
            $streetName = null;
        }
        $houseNumber = $registryBuildingEntrance->streetHouseNumber;
        if ('' === $houseNumber) {
            $houseNumber = null;
        }

        $street = null;
        if (null !== $streetName || null !== $houseNumber) {
            $street = StreetFactory::createFromSeparateStrings($streetName, $houseNumber);
        }

        $streetAbbreviated = null;
        if ('' !== $registryBuildingEntrance->streetNameAbbreviation) {
            $streetAbbreviated = StreetFactory::createFromSeparateStrings($registryBuildingEntrance->streetNameAbbreviation, $houseNumber);
        }

        return new BuildingEntranceData(
            countryCode: CountryCodeEnum::from($registryBuildingEntrance->countryCode->value),
            buildingId: $registryBuildingEntrance->buildingId,
            entranceId: $registryBuildingEntrance->entranceId,
            addressId: $registryBuildingEntrance->addressId,
            streetNameLanguage: LanguageEnum::from($registryBuildingEntrance->streetNameLanguage->value),
            streetId: $registryBuildingEntrance->streetId,
            street: $street,
            streetAbbreviated: $streetAbbreviated,
            postalCode: $registryBuildingEntrance->postalCode,
            locality: $registryBuildingEntrance->locality,
            municipality: $registryBuildingEntrance->municipality,
            municipalityCode: $registryBuildingEntrance->municipalityCode,
            cantonCode: $registryBuildingEntrance->cantonCode,
            geoCoordinateEastLV95: $registryBuildingEntrance->geoCoordinateEastLV95,
            geoCoordinateNorthLV95: $registryBuildingEntrance->geoCoordinateNorthLV95,
        );
    }
}
