<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Scheduler;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use App\Domain\Registry\Contract\RegistryDataDownloaderProviderInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

abstract readonly class AbstractRegistryDataRefresh
{
    protected const int ACTIVE_DAYS_WINDOW = 90;

    public function __construct(
        private RegistryDataDownloaderProviderInterface $downloaderProvider,
        private BuildingEntranceImporterInterface $importer,
        private BuildingEntranceWriteRepositoryInterface $buildingEntranceWriteRepository,
        #[Autowire(env: 'bool:REGISTRY_DATABASE_REFRESH_ENABLED')]
        private bool $enabled,
    ) {}

    abstract protected function country(): CountryCodeEnum;

    public function __invoke(): void
    {
        if (!$this->enabled) {
            return;
        }

        $countryCode = $this->country();
        $this->downloaderProvider->getDownloader($countryCode)->download();

        foreach ($this->importer->importBuildingData($countryCode) as $buildingData) {
            // Loop for import to happen
        }

        $this->buildingEntranceWriteRepository->deleteOutdatedBuildingEntrances(self::ACTIVE_DAYS_WINDOW, $countryCode);
    }
}
