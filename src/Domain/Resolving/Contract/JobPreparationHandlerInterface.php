<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

interface JobPreparationHandlerInterface
{
    /**
     * Prepares the data of the given resolver job.
     *
     * @throws ResolverJobNotFoundException
     */
    public function handlePreparation(ResolverJobIdentifier $job): void;
}
