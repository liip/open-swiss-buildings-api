<?php

declare(strict_types=1);

namespace App\Infrastructure\Csv;

use App\Domain\Resolving\Contract\CsvReaderFactoryInterface;
use App\Domain\Resolving\Contract\CsvReaderInterface;

final class PhpCsvReaderFactory implements CsvReaderFactoryInterface
{
    public function createReader($data, ?string $delimiter = null, ?string $enclosure = null, ?string $charset = null): CsvReaderInterface
    {
        return new PhpCsvReader($data, $delimiter, $enclosure, $charset);
    }
}
