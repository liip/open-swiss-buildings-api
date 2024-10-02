<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\ResolverAddress;
use Symfony\Component\Uid\Uuid;

interface ResolverAddressReadRepositoryInterface
{
    /**
     * Returns a list of addresses for the given job, that are not matched yet.
     *
     * @return iterable<ResolverAddress>
     */
    public function getNonMatchedAddresses(Uuid $jobId): iterable;

    /**
     * Returns a list of addresses for the given job, that are not matched yet and don't have a street assigned.
     *
     * @return iterable<ResolverAddress>
     */
    public function getNonMatchedAddressesWithoutStreet(Uuid $jobId): iterable;
}
