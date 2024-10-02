<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Symfony\Component\Uid\Uuid;

interface ResolverJobReadRepositoryInterface
{
    /**
     * Returns a list of all resolver jobs in an unordered way.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverJob>
     */
    public function getJobs(): iterable;

    /**
     * Returns a list of resolver jobs, that are expired at the given time, in an unordered way.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverJobIdentifier>
     */
    public function getExpiredJobs(\DateTimeInterface $now): iterable;

    /**
     * Returns the resolver job identifier with the given ID.
     *
     * @throws ResolverJobNotFoundException
     */
    public function getJobIdentifier(Uuid $id): ResolverJobIdentifier;

    /**
     * Returns information about the resolver job with the given ID.
     *
     * @throws ResolverJobNotFoundException
     */
    public function getJobInfo(Uuid $id): ResolverJob;
}
