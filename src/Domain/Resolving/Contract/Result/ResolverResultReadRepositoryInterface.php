<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Result;

use App\Domain\Resolving\Model\Result\ResolverResult;
use App\Infrastructure\Pagination;
use Symfony\Component\Uid\Uuid;

interface ResolverResultReadRepositoryInterface
{
    /**
     * Returns all the results for a given resolver job sorted by building ID.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverResult>
     */
    public function getResults(Uuid $jobId): iterable;

    /**
     * Returns a limited set of the results for a given resolver job sorted by building ID.
     *
     * The returned list might also be empty.
     *
     * @return iterable<ResolverResult>
     */
    public function getPaginatedResults(Uuid $jobId, Pagination $pagination): iterable;
}
