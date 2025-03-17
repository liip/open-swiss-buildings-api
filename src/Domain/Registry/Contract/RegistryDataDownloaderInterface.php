<?php

declare(strict_types=1);

namespace App\Domain\Registry\Contract;

use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(RegistryDataDownloaderInterface::class)]
interface RegistryDataDownloaderInterface
{
    /**
     * Identify the country this registry provides.
     */
    public static function country(): CountryCodeEnum;

    /**
     * Downloads the registry data and puts the database at the correct place.
     *
     * @return bool Whether the new building data registry was downloaded
     */
    public function download(): bool;

    /**
     * Returns the filename of the database file for the handled registry.
     */
    public function getDatabaseFilename(): string;
}
