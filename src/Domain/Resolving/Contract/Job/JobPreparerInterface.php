<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface to mark a class able to Prepare a give Job.
 */
#[AutoconfigureTag(JobPreparerInterface::class)]
interface JobPreparerInterface
{
    /**
     * Prepares the data of a specific resolver job type.
     *
     * @throws ResolverJobNotFoundException
     * @throws InvalidInputDataException
     */
    public function prepareJob(ResolverJobRawData $jobData): void;

    /**
     * Returns whether this preparer is able to prepare the given type.
     */
    public function canPrepareJob(ResolverTypeEnum $type): bool;
}
