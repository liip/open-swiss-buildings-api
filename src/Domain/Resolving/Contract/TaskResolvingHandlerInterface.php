<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

interface TaskResolvingHandlerInterface
{
    /**
     * Processes the given resolver job.
     *
     * @throws ResolverJobNotFoundException|RetryableResolvingErrorException
     */
    public function handleResolving(ResolverJobIdentifier $job): void;
}
