<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Contract;

interface DataExtractorInterface
{
    /**
     * Extracts the SQLite database from the Swiss Building data.
     *
     * @param string $source path to ZIP file as downloaded from the BFS server
     * @param string $target path where to extract the SQLite file from the ZIP file to
     */
    public function extractSqlite(string $source, string $target): void;
}
