<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\ResolverTask;
use App\Infrastructure\Pagination;
use Symfony\Component\Uid\Uuid;

interface ResolverTaskReadRepositoryInterface
{
    /**
     * Returns a list of all tasks for a given resolver job in an unordered way.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverTask>
     */
    public function getTasks(Uuid $jobId): iterable;

    /**
     * Returns a limited set of tasks for a given resolver job in an unordered way.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverTask>
     */
    public function getPaginatedTasks(Uuid $jobId, Pagination $pagination): iterable;

    /**
     * @return iterable<Uuid>
     */
    public function getTasksIds(Uuid $jobId): iterable;
}
