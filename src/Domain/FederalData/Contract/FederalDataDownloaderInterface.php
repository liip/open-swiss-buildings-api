<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Contract;

interface FederalDataDownloaderInterface
{
    /**
     * Downloads the Swiss Building data and puts the SQLite database at the correct place.
     *
     * @return bool Whether the new building data was downloaded
     */
    public function download(): bool;

    /**
     * Returns the filename of the SQLite database for the federal data.
     */
    public function getDatabaseFilename(): string;
}
