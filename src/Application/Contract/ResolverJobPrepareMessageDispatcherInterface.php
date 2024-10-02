<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

interface ResolverJobPrepareMessageDispatcherInterface
{
    public function dispatchJobForPreparation(ResolverJobIdentifier $jobId): void;
}
