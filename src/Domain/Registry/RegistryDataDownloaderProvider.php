<?php

declare(strict_types=1);

namespace App\Domain\Registry;

use App\Domain\Registry\Contract\RegistryDataDownloaderInterface;
use App\Domain\Registry\Contract\RegistryDataDownloaderProviderInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class RegistryDataDownloaderProvider implements RegistryDataDownloaderProviderInterface
{
    public function __construct(
        /**
         * @var iterable<RegistryDataDownloaderInterface> $dataDownloader
         */
        #[AutowireIterator(RegistryDataDownloaderInterface::class)]
        private iterable $dataDownloader,
    ) {}

    public function getAllDownloader(): iterable
    {
        yield from $this->dataDownloader;
    }

    public function getDownloader(CountryCodeEnum $countryCode): RegistryDataDownloaderInterface
    {
        foreach ($this->dataDownloader as $countryDataDownloader) {
            if ($countryDataDownloader::country() !== $countryCode) {
                continue;
            }

            return $countryDataDownloader;
        }

        throw new \InvalidArgumentException("Unable to find data downloader for country: {$countryCode->value}");
    }
}
