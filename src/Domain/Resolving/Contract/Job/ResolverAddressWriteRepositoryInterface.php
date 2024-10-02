<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\WriteResolverAddress;
use Symfony\Component\Uid\Uuid;

interface ResolverAddressWriteRepositoryInterface
{
    /**
     * Stores the given addresses.
     *
     * @param iterable<WriteResolverAddress> $addresses
     */
    public function store(iterable $addresses): void;

    public function deleteByJobId(Uuid $jobId): int;
}
