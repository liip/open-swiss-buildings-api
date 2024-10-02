<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\ResolverMetadata;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for updating the metadata of resolver jobs.
 */
interface ResolverMetadataWriteRepositoryInterface
{
    /**
     * Updates the metadata of the given resolver job.
     */
    public function updateMetadata(Uuid $id, ResolverMetadata $metadata): void;
}
