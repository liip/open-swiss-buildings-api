<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\WriteResolverAddressStreet;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for adding address street matches.
 */
interface ResolverAddressStreetWriteRepositoryInterface
{
    /**
     * Stores the given address streets.
     *
     * @param iterable<WriteResolverAddressStreet> $addressStreets
     */
    public function store(iterable $addressStreets): void;

    public function deleteByJobId(Uuid $jobId): int;
}
