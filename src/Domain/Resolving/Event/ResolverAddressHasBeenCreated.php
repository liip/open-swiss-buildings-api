<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Event;

use Symfony\Component\Uid\Uuid;

final readonly class ResolverAddressHasBeenCreated
{
    public function __construct(
        public Uuid $addressId,
    ) {}
}
