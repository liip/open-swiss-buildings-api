<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Event;

use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Failure\ResolverJobFailureEnum;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;

final readonly class JobResolvingHasFailed
{
    public function __construct(
        public ResolverJobIdentifier $job,
        public ResolverJobFailure $failure,
    ) {}

    public static function fromException(ResolverJobIdentifier $job, \Throwable $e): self
    {
        return new self(
            $job,
            ResolverJobFailure::fromException(ResolverJobFailureEnum::RESOLVING_ERROR, $e),
        );
    }
}
