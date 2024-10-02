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
         * @var array<value-of<CantonCodeEnum>, non-negative-int>
         */
        public array $byCanton,
    ) {}
}
