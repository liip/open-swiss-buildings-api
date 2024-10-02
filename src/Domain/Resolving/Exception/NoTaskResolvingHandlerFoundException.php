<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Uid\Uuid;

final class NoTaskResolvingHandlerFoundException extends ResolvingErrorException
{
    public function __construct(Uuid $jobId, ResolverTypeEnum $type)
    {
        parent::__construct("No resolving handler found for job {$jobId} of type {$type->value}");
    }
}
