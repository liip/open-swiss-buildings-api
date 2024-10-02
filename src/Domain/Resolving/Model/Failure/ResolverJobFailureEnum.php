<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Failure;

enum ResolverJobFailureEnum: string
{
    case NO_JOB_PREPARER = 'No job preparer found';
    case NO_TASK_RESOLVER = 'No task resolver found';
    case INVALID_DATA = 'Invalid input data provided';
    case RESOLVING_ERROR = 'Error during resolving';
}
