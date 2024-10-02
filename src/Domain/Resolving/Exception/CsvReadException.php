<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Exception;

final class CsvReadException extends ResolvingErrorException
{
    public function __construct(string $details)
    {
        parent::__construct("CSV data could not be read ({$details})");
    }
}
