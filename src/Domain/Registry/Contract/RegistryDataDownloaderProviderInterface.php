<?php

declare(strict_types=1);

namespace App\Domain\Registry\Contract;

use App\Infrastructure\Model\CountryCodeEnum;

interface RegistryDataDownloaderProviderInterface
{
    public function getDownloader(CountryCodeEnum $countryCode): RegistryDataDownloaderInterface;

    /**
     * @return iterable<RegistryDataDownloaderInterface>
     */
    public function getAllDownloader(): iterable;
}
