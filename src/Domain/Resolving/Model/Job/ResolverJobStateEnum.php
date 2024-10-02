<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

enum ResolverJobStateEnum: string
{
    /**
     * The Job has been created.
     */
    case CREATED = 'created';

    /**
     * The Job is being prepared and validated.
     */
    case PREPARING = 'preparing';

    /**
     * The Job is ready for data resolving, it will be processed for resolving soon.
     */
    case READY = 'ready';

    /**
     * The Job is being processed and the resolving being executed.
     */
    case RESOLVING = 'resolving';

    /**
     * The Job is completed, and the results available.
     */
    case COMPLETED = 'completed';

    /**
     * The Job has failed.
     */
    case FAILED = 'failed';
}
