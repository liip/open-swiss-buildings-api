<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Model\CsvRow;

interface CsvReaderInterface
{
    /**
     * Returns the header of the CSV.
     *
     * @return non-empty-list<non-empty-string>
     *
     * @throws CsvReadException
     */
    public function getHeader(): array;

    /**
     * Returns the CSV delimiter character, one single-byte character only.
     *
     * @return non-empty-string
     */
    public function getDelimiter(): string;

    /**
     * Returns the CSV enclosure character, one single-byte character only.
     *
     * @return non-empty-string
     */
    public function getEnclosure(): string;

    /**
     * Reads data from the CSV and returns the array representation of each row.
     *
     * @return iterable<CsvRow>
     *
     * @throws CsvReadException
     */
    public function read(): iterable;
}
