<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Event;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

final readonly class JobResolvingHasCompleted
{
    public function __construct(
        public ResolverJobIdentifier $job,
    ) {}
}
