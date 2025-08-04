<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH;

use App\Domain\Registry\Contract\RegistryDataDownloaderInterface;
use App\Domain\Registry\DataCH\Contract\RegistryDatabaseExtractorInterface;
use App\Domain\Registry\DataCH\Contract\RegistryDownloaderInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final readonly class RegistryDataDownloader implements RegistryDataDownloaderInterface
{
    public function __construct(
        private RegistryDownloaderInterface $downloader,
        private RegistryDatabaseExtractorInterface $extractor,
        private Filesystem $filesystem,
        #[Autowire(value: '%env(resolve:REGISTRY_DATABASE_CH_FILE)%')]
        private string $databaseFile,
    ) {}

    public static function country(): CountryCodeEnum
    {
        return CountryCodeEnum::CH;
    }

    public function download(?ProgressBar $progressBar = null): bool
    {
        $zipFilename = $this->getDatabaseFilename() . '.zip';

        $downloaded = $this->downloader->download(target:
            $zipFilename,
            force: !$this->filesystem->exists($this->getDatabaseFilename()),
            progressBar: $progressBar,
        );
        if ($downloaded) {
            $this->extractor->extractDatabase($zipFilename, $this->getDatabaseFilename());
            $this->filesystem->remove($zipFilename);
        }

        return $downloaded;
    }

    public function getDatabaseFilename(): string
    {
        return $this->databaseFile;
    }
}
