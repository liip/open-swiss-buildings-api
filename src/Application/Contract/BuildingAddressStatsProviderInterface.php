<?php

declare(strict_types=1);

namespace App\Application\Contract;

interface BuildingAddressStatsProviderInterface
{
    public function countIndexedAddresses(): int;
}
