<?php

declare(strict_types=1);

namespace App\Domain\BuildingData;

use App\Domain\BuildingData\Contract\BuildingDataBridgedFactoryInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenImported;
use App\Infrastructure\Model\CountryCodeEnum;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class BuildingEntranceImporter implements BuildingEntranceImporterInterface
{
    public function __construct(
        private BuildingDataBridgedFactoryInterface $buildingDataFactory,
        private BuildingEntranceWriteRepositoryInterface $entranceWriteRepository,
        private EventDispatcherInterface $eventDispatcher,
        private ClockInterface $clock,
    ) {}

    public function countBuildingEntrances(?CountryCodeEnum $countryCode = null): int
    {
        return $this->buildingDataFactory->countBuildingData($countryCode);
    }

    public function importBuildingData(?CountryCodeEnum $countryCode = null): iterable
    {
        $timestamp = $this->clock->now();

        yield from $this->entranceWriteRepository->store(
            $this->buildingDataFactory->getBuildingData($countryCode),
        );

        $this->eventDispatcher->dispatch(new BuildingEntrancesHaveBeenImported($timestamp));
    }

    public function importManualBuildingData(iterable $buildingData): void
    {
        $timestamp = $this->clock->now();

        foreach ($this->entranceWriteRepository->store($buildingData) as $buildingEntrance) {
            // Loop for import to happen
        }

        $this->eventDispatcher->dispatch(new BuildingEntrancesHaveBeenImported($timestamp));
    }
}
