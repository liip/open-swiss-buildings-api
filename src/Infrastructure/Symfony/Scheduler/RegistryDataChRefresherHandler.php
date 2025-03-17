<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Scheduler;

use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

// Every Monday, at 10:00
#[AsCronTask('0 10 * * 1')]
final readonly class RegistryDataChRefresherHandler extends AbstractRegistryDataRefresh
{
    protected function country(): CountryCodeEnum
    {
        return CountryCodeEnum::CH;
    }
}
