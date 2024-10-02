<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Contract;

interface DataDownloaderInterface
{
    /**
     * Downloads the Swiss Building data as a ZIP file to the defined target filename.
     *
     * The building data should only be downloaded if the source file on the server changed.
     *
     * @return bool Whether the new building data was downloaded
     */
    public function download(string $target, bool $force = false): bool;
}
