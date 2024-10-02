<?php

declare(strict_types=1);

namespace App\Domain\FederalData;

use App\Domain\FederalData\Contract\DataDownloaderInterface;
use App\Domain\FederalData\Contract\DataExtractorInterface;
use App\Domain\FederalData\Contract\FederalDataDownloaderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final readonly class FederalDataDownloader implements FederalDataDownloaderInterface
{
    public function __construct(
        private DataDownloaderInterface $downloader,
        private DataExtractorInterface $extractor,
        private Filesystem $filesystem,
        #[Autowire(value: '%env(resolve:FEDERAL_DATABASE_FILE)%')]
        private string $databaseFile,
    ) {}

    public function download(): bool
    {
        $zipFilename = $this->getDatabaseFilename() . '.zip';

        $downloaded = $this->downloader->download(target: $zipFilename, force: !$this->filesystem->exists($this->getDatabaseFilename()));
        if ($downloaded) {
            $this->extractor->extractSqlite($zipFilename, $this->getDatabaseFilename());
            $this->filesystem->remove($zipFilename);
        }

        return $downloaded;
    }

    public function getDatabaseFilename(): string
    {
        return $this->databaseFile;
    }
}
