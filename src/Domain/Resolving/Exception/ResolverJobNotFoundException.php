<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

use Symfony\Component\Uid\Uuid;

final class ResolverJobNotFoundException extends ResolvingErrorException
{
    public function __construct(
        public readonly Uuid $jobId,
    ) {
        parent::__construct("Resolver job with ID {$this->jobId} was not found");
    }
}
