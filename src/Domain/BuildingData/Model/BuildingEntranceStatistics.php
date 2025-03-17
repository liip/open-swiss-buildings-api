<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

final readonly class BuildingEntranceStatistics
{
    public function __construct(
        /**
         * @var non-negative-int
         */
        public int $total,
        /**
         * @var array<string, non-negative-int>
         */
        public array $byCanton,
    ) {}
}
