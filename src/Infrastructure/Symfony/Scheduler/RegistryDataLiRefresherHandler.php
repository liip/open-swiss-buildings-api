<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Scheduler;

use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

// Every Monday, at 08:30
#[AsCronTask('30 08 * * 1')]
final readonly class RegistryDataLiRefresherHandler extends AbstractRegistryDataRefresh
{
    protected function country(): CountryCodeEnum
    {
        return CountryCodeEnum::LI;
    }
}
