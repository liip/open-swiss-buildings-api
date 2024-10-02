<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

interface CsvReaderFactoryInterface
{
    /**
     * Creates an instance of a CSV reader with the given resource.
     *
     * @param resource              $data
     * @param non-empty-string|null $delimiter Optional delimiter for CSV fields, one single-byte character only
     * @param non-empty-string|null $enclosure Optional enclosure for CSV values, one single-byte character only
     * @param non-empty-string|null $charset
     */
    public function createReader($data, ?string $delimiter = null, ?string $enclosure = null, ?string $charset = null): CsvReaderInterface;
}
