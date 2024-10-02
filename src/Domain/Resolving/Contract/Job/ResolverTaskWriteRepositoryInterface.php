<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\WriteResolverTask;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for adding tasks data.
 */
interface ResolverTaskWriteRepositoryInterface
{
    /**
     * Stores the given tasks.
     *
     * @param iterable<WriteResolverTask> $tasks
     */
    public function store(iterable $tasks): void;

    public function deleteByJobId(Uuid $jobId): int;
}
