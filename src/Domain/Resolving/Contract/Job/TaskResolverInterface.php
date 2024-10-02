<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Job;

use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(TaskResolverInterface::class)]
interface TaskResolverInterface
{
    /**
     * Resolves the tasks of the given job.
     *
     * Each task could be resolved into zero, one or more results.
     *
     * The resulting entries are stored in the result table.
     */
    public function resolveTasks(ResolverJobIdentifier $job): void;

    /**
     * Returns whether this handler is able to resolve the given type.
     */
    public function canResolveTasks(ResolverTypeEnum $type): bool;
}
