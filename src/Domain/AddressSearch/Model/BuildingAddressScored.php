<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

final class BuildingAddressScored
{
    public function __construct(
        /**
         * @var int<0, 100>
         */
        public int $score,
        public string $matchingHighlight,
        public BuildingAddress $buildingAddress,
        /**
         * @var list<string>|null
         */
        public ?array $rankingScoreDetails = null,
    ) {}
}
