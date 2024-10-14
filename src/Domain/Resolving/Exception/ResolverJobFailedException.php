<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

final class ResolverJobFailedException extends ResolvingErrorException
{
    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public static function wrap(\Throwable $previous): self
    {
        return new self("Failed to resolve job: {$previous->getMessage()}", $previous);
    }

    public static function enhance(string $message, self $previous): self
    {
        return new self($message, $previous);
    }
}
