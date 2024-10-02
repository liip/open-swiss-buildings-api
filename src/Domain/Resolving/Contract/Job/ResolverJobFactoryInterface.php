<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;

interface ResolverJobFactoryInterface
{
    /**
     * Creates a resolver job instance.
     *
     * @param resource          $data     raw input data for the job
     * @param ?ResolverMetadata $metadata metadata or configuration about the resolver job
     */
    public function create(ResolverTypeEnum $type, $data, ?ResolverMetadata $metadata = null): ResolverJobIdentifier;
}
