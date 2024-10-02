<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

use App\Infrastructure\Address\Model\Street;

final readonly class BuildingEntranceAddress
{
    public function __construct(
        public string $referenceId,
        public Street $street,
        /**
         * @var non-empty-string
         */
        public string $postalCode,
        /**
         * @var non-empty-string
         */
        public string $locality,
    ) {}
}
