<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Contract;

interface RegistryDatabaseExtractorInterface
{
    /**
     * Extracts the SQLite database from the Swiss Building data.
     *
     * @param string $source path to ZIP file as downloaded from the BFS server
     * @param string $target path where to extract the SQLite file from the ZIP file to
     */
    public function extractDatabase(string $source, string $target): void;
}
