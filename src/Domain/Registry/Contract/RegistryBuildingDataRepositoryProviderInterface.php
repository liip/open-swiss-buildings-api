<?php

declare(strict_types=1);

namespace App\Domain\Registry\Contract;

use App\Infrastructure\Model\CountryCodeEnum;

interface RegistryBuildingDataRepositoryProviderInterface
{
    public function getRepository(CountryCodeEnum $countryCode): RegistryBuildingDataRepositoryInterface;

    /**
     * @return iterable<RegistryBuildingDataRepositoryInterface>
     */
    public function getAllRepositories(): iterable;
}
