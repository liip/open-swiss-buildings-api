<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH;

use App\Domain\Registry\AbstractRegistryDatabaseExtractor;
use App\Domain\Registry\DataCH\Contract\RegistryDatabaseExtractorInterface;

final readonly class RegistryDatabaseExtractor extends AbstractRegistryDatabaseExtractor implements RegistryDatabaseExtractorInterface
{
    protected function getCompressedDatabaseFilename(): string
    {
        return 'data.sqlite';
    }
}
