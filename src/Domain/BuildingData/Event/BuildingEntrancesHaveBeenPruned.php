<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Event;

use App\Infrastructure\Model\CountryCodeEnum;

final readonly class BuildingEntrancesHaveBeenPruned
{
    public function __construct(
        public \DateTimeImmutable $importedAtBefore,
        public ?CountryCodeEnum $countryCode,
    ) {}
}
