<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI\Contract;

interface RegistryDownloaderInterface
{
    /**
     * Downloads the building registry to the defined target filename.
     *
     * The building registry should only be downloaded if the source file on the server changed.
     *
     * @return bool Whether the new building data was downloaded
     */
    public function download(string $target, bool $force = false): bool;
}
