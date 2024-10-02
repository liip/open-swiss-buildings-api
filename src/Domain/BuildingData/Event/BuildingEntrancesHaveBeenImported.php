<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Event;

final readonly class BuildingEntrancesHaveBeenImported
{
    public function __construct(
        public \DateTimeImmutable $timestamp,
    ) {}
}
