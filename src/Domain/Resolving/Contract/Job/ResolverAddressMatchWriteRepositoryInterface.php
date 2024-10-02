<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\WriteResolverAddressMatch;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for adding address matches.
 */
interface ResolverAddressMatchWriteRepositoryInterface
{
    /**
     * Stores the given address matches.
     *
     * @param iterable<WriteResolverAddressMatch> $matches
     */
    public function store(iterable $matches): void;

    public function deleteByJobId(Uuid $jobId): int;
}
