<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Event;

final readonly class BuildingEntrancesHaveBeenPruned
{
    public function __construct(
        public \DateTimeImmutable $importedAtBefore,
    ) {}
}
