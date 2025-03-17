<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI\Contract;

interface RegistryDatabaseExtractorInterface
{
    /**
     * Extracts the building registry database from the downloaded file.
     *
     * @param string $source path to the downloaded file
     * @param string $target path where to extract/move the database file to
     */
    public function extractDatabase(string $source, string $target): void;
}
