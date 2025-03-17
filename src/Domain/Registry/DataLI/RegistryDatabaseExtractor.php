<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI;

use App\Domain\Registry\AbstractRegistryDatabaseExtractor;
use App\Domain\Registry\DataLI\Contract\RegistryDatabaseExtractorInterface;

final readonly class RegistryDatabaseExtractor extends AbstractRegistryDatabaseExtractor implements RegistryDatabaseExtractorInterface
{
    protected function getCompressedDatabaseFilename(): string
    {
        return 'gwrli.csv';
    }
}
