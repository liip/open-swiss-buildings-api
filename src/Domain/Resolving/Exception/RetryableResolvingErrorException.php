<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

final class RetryableResolvingErrorException extends ResolvingErrorException
{
    public static function fromException(\Throwable $e): self
    {
        return new self("A retryable failure happened, the task will be retried later (error: {$e->getMessage()})", 0, $e);
    }
}
