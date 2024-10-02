<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use Symfony\Component\Uid\Uuid;

final readonly class WriteResolverAddressStreet
{
    public function __construct(
        public Uuid $addressId,
        /**
         * @var non-empty-string
         */
        public string $streetId,
        /**
         * @var int<0, 100>
         */
        public int $confidence,
        public string $matchType,
    ) {}
}
