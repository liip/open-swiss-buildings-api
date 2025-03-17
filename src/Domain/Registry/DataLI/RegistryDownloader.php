<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI;

use App\Domain\Registry\AbstractRegistryDownloader;
use App\Domain\Registry\DataLI\Contract\RegistryDownloaderInterface;

final readonly class RegistryDownloader extends AbstractRegistryDownloader implements RegistryDownloaderInterface
{
    private const string URL = 'https://service.geo.llv.li/download/getfile.php?theme=gwr';

    protected function getRegistryURL(): string
    {
        return self::URL;
    }
}
