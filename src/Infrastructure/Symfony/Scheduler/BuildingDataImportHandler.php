<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Scheduler;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use App\Domain\FederalData\Contract\FederalDataDownloaderInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 'Sunday midnight +9 hours')]
final readonly class BuildingDataImportHandler
{
    private const int ACTIVE_DAYS_WINDOW = 90;

    public function __construct(
        private FederalDataDownloaderInterface $downloader,
        private BuildingEntranceImporterInterface $importer,
        private BuildingEntranceWriteRepositoryInterface $buildingEntranceWriteRepository,
    ) {}

    public function __invoke(): void
    {
        $this->downloader->download();

        foreach ($this->importer->importBuildingData() as $buildingData) {
            // Loop for import to happen
        }

        $this->buildingEntranceWriteRepository->deleteOutdatedBuildingEntrances(self::ACTIVE_DAYS_WINDOW);
    }
}
