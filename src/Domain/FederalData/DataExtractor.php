<?php

declare(strict_types=1);

namespace App\Domain\FederalData;

use App\Domain\FederalData\Contract\DataExtractorInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class DataExtractor implements DataExtractorInterface
{
    private const string SQLITE_IN_ZIP = 'data.sqlite';

    private \ZipArchive $zipArchive;
    private Filesystem $filesystem;

    public function __construct(
        ?\ZipArchive $zipArchive = null,
        ?Filesystem $filesystem = null,
    ) {
        $this->zipArchive = $zipArchive ?? new \ZipArchive();
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function extractSqlite(string $source, string $target): void
    {
        if (!$this->filesystem->exists($source)) {
            throw new \UnexpectedValueException("Expected source ZIP file at {$source} to be existing");
        }

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/' . self::SQLITE_IN_ZIP;

        $this->zipArchive->open($source);
        $this->zipArchive->extractTo($tempDir, [self::SQLITE_IN_ZIP]);

        if (!$this->filesystem->exists($tempFile)) {
            throw new \UnexpectedValueException('ZIP file does not contain the file "' . self::SQLITE_IN_ZIP . '"');
        }

        $this->filesystem->rename($tempFile, $target, true);
    }
}
