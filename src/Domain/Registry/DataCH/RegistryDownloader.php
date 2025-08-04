<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH;

use App\Domain\Registry\AbstractRegistryDownloader;
use App\Domain\Registry\DataCH\Contract\RegistryDownloaderInterface;

final class RegistryDownloader extends AbstractRegistryDownloader implements RegistryDownloaderInterface
{
    private const string URL = 'https://public.madd.bfs.admin.ch/ch.zip';

    protected function getRegistryURL(): string
    {
        return self::URL;
    }
}
