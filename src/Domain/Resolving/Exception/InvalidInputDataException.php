<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

final class InvalidInputDataException extends ResolvingErrorException
{
    public function __construct(string $details, ?\Throwable $previous = null)
    {
        parent::__construct("Invalid data provided for the job: {$details}", 0, $previous);
    }

    public static function wrap(\Throwable $previous): self
    {
        return new self($previous->getMessage(), $previous);
    }
}
