<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for adding or changing resolver jobs.
 */
interface ResolverJobWriteRepositoryInterface
{
    /**
     * Adds a new resolver job, stores it and returns the ID.
     *
     * @param resource         $data     raw input data for the job
     * @param ResolverMetadata $metadata metadata or configuration about the resolver job
     */
    public function add(
        ResolverTypeEnum $type,
        $data,
        ResolverMetadata $metadata,
    ): Uuid;

    /**
     * Marks the given resolver job as created.
     */
    public function markJobAsCreated(Uuid $id): void;

    /**
     * Marks the given resolver job as preparing.
     */
    public function markJobAsPreparing(Uuid $id): void;

    /**
     * Marks the given resolver job as ready.
     */
    public function markJobAsReady(Uuid $id): void;

    /**
     * Marks the given resolver job as processing.
     */
    public function markJobAsResolving(Uuid $id): void;

    /**
     * Marks the given resolver job as completed.
     */
    public function markJobAsCompleted(Uuid $id): void;

    /**
     * Marks the given resolver job as failed.
     */
    public function markJobAsFailed(Uuid $id, ResolverJobFailure $failure): void;

    /**
     * Marks the given resolver job as failed by updating the failure, while keeping the current status.
     */
    public function markJobAsTemporarilyFailed(Uuid $id, ResolverJobFailure $failure): void;

    /**
     * Removes the given resolver job.
     */
    public function delete(Uuid $id): void;
}
