<?php

declare(strict_types=1);

namespace App\Application\Web\Contract;

use App\Domain\Resolving\Model\Job\ResolverJob;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

interface JobResultResponseCreatorInterface
{
    public function buildResponse(Uuid $jobId, ResolverJob $job): Response;
}
