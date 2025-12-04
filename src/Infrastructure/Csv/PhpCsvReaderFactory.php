<?php

declare(strict_types=1);

namespace App\Infrastructure\Csv;

use App\Domain\Resolving\Contract\CsvReaderFactoryInterface;

final class PhpCsvReaderFactory implements CsvReaderFactoryInterface
{
    public function createReader($data, ?string $delimiter = null, ?string $enclosure = null, ?string $charset = null): PhpCsvReader
    {
        return new PhpCsvReader($data, $delimiter, $enclosure, $charset);
    }
}
