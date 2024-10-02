<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Result;

use Symfony\Component\Uid\Uuid;

interface ResolverResultWriteRepositoryInterface
{
    public function deleteByJobId(Uuid $jobId): int;
}
