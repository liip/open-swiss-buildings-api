<?php

declare(strict_types=1);

namespace App\Domain\Registry;

use Symfony\Component\Filesystem\Filesystem;

abstract readonly class AbstractRegistryDatabaseExtractor
{
    private \ZipArchive $zipArchive;

    private Filesystem $filesystem;

    public function __construct(
        ?\ZipArchive $zipArchive = null,
        ?Filesystem $filesystem = null,
    ) {
        $this->zipArchive = $zipArchive ?? new \ZipArchive();
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    abstract protected function getCompressedDatabaseFilename(): string;

    final public function extractDatabase(string $source, string $target): void
    {
        if (!$this->filesystem->exists($source)) {
            throw new \UnexpectedValueException("Expected source ZIP file at {$source} to be existing");
        }

        $compressedDatabaseFilename = $this->getCompressedDatabaseFilename();
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/' . $compressedDatabaseFilename;

        $this->zipArchive->open($source);
        $this->zipArchive->extractTo($tempDir, [$compressedDatabaseFilename]);

        if (!$this->filesystem->exists($tempFile)) {
            throw new \UnexpectedValueException("Source file does not contain the file {$compressedDatabaseFilename}");
        }

        $this->filesystem->rename($tempFile, $target, true);
    }
}
