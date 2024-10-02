<?php

declare(strict_types=1);

namespace App\Application\Messaging\Message;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

final readonly class ResolverResolveJobMessage implements AsyncMessage
{
    public function __construct(
        public ResolverJobIdentifier $job,
    ) {}
}
