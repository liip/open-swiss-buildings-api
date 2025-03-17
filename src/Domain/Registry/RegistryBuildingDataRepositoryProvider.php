<?php

declare(strict_types=1);

namespace App\Domain\Registry;

use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryInterface;
use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryProviderInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class RegistryBuildingDataRepositoryProvider implements RegistryBuildingDataRepositoryProviderInterface
{
    public function __construct(
        /**
         * @var iterable<RegistryBuildingDataRepositoryInterface> $repository
         */
        #[AutowireIterator(RegistryBuildingDataRepositoryInterface::class)]
        private iterable $repository,
    ) {}

    public function getAllRepositories(): iterable
    {
        yield from $this->repository;
    }

    public function getRepository(CountryCodeEnum $countryCode): RegistryBuildingDataRepositoryInterface
    {
        foreach ($this->repository as $registryBuildingDataRepository) {
            if ($registryBuildingDataRepository::country() !== $countryCode) {
                continue;
            }

            return $registryBuildingDataRepository;
        }

        throw new \InvalidArgumentException("Unable to find registry for country: {$countryCode->value}");
    }
}
