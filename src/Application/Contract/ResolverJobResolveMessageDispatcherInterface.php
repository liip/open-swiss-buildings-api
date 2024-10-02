<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

interface ResolverJobResolveMessageDispatcherInterface
{
    public function dispatchJobForResolving(ResolverJobIdentifier $jobId): void;
}
