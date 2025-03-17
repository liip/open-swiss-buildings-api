<?php

declare(strict_types=1);

namespace App\Infrastructure\Csv;

use App\Domain\Resolving\Contract\CsvReaderInterface;
use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Model\CsvRow;
use Symfony\Component\String\ByteString;

final class PhpCsvReader implements CsvReaderInterface
{
    private const string DELIMITER_DEFAULT = ',';
    private const array DELIMITER_COMMON = [
        ',',
        ';',
        "\t",
    ];
    private const string ENCLOSURE_DEFAULT = '"';
    private const array ENCLOSURE_COMMON = [
        '"',
        "'",
    ];

    private const int LINES_FOR_GUESSING = 3;

    private const array BOMS = [
        "\xFF\xFE\x00\x00", // UTF-32 Le
        "\x00\x00\xFE\xFF", // UTF-32 Be
        "\xFE\xFF", // UTF-16 Le
        "\xFF\xFE", // UTF-16 Be
        "\xEF\xBB\xBF", // UTF-8
    ];

    /**
     * @var list<string>|null
     */
    private ?array $firstRawLines = null;

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    private ?array $header = null;

    public function __construct(
        /**
         * @var resource
         */
        private $resource,
        /**
         * @var non-empty-string|null
         */
        private ?string $delimiter = null,
        /**
         * @var non-empty-string|null
         */
        private ?string $enclosure = null,
        /**
         * @var non-empty-string|null
         */
        private readonly ?string $charset = null,
        private readonly string $escape = '\\',
    ) {
        if (!\is_resource($this->resource)) {
            throw new \InvalidArgumentException('Resource is closed');
        }
        if (null !== $this->delimiter && 1 !== \strlen($this->delimiter)) {
            throw new \InvalidArgumentException('CSV delimiter needs to be one single-byte character');
        }
        if (null !== $this->enclosure && 1 !== \strlen($this->enclosure)) {
            throw new \InvalidArgumentException('CSV enclosure needs to be one single-byte character');
        }
    }

    public function getDelimiter(): string
    {
        $this->prepare();

        return $this->delimiter;
    }

    public function getEnclosure(): string
    {
        $this->prepare();

        return $this->enclosure;
    }

    public function getHeader(): array
    {
        $this->prepare();

        return $this->header;
    }

    public function read(): iterable
    {
        $this->prepare();

        $rowNumber = 2;

        // First, yield the rows which are already in memory, excluding the header
        foreach (\array_slice($this->firstRawLines, 1) as $line) {
            $row = $this->buildRow($rowNumber, $line, $this->delimiter, $this->enclosure);

            try {
                yield CsvRow::fromCsv($rowNumber, $row, $this->header);
            } catch (\InvalidArgumentException $e) {
                throw new CsvReadException("Could not read row #{$rowNumber} ({$e->getMessage()})");
            }

            ++$rowNumber;
        }

        while (false !== ($line = fgets($this->resource))) {
            // We make sure that the line is properly encoded in UTF-8, given the (optional) charset
            $line = (new ByteString($line))->toCodePointString($this->charset)->toString();
            $row = $this->buildRow($rowNumber, $line, $this->delimiter, $this->enclosure);
            try {
                yield CsvRow::fromCsv($rowNumber, $row, $this->header);
            } catch (\InvalidArgumentException $e) {
                throw new CsvReadException("Could not read row #{$rowNumber} ({$e->getMessage()})");
            }

            ++$rowNumber;
        }
    }

    /**
     * @phpstan-assert !null $this->delimiter
     * @phpstan-assert !null $this->enclosure
     * @phpstan-assert !null $this->header
     * @phpstan-assert !null $this->firstRawLines
     */
    private function prepare(): void
    {
        $linesToExtract = self::LINES_FOR_GUESSING;
        if (null !== $this->delimiter && null !== $this->enclosure) {
            $linesToExtract = 1;
        }
        $this->extractFirstLines($linesToExtract);

        if (null === $this->delimiter) {
            $this->delimiter = $this->guessDelimiter();
        }

        if (null === $this->enclosure) {
            $this->enclosure = $this->guessEnclosure();
        }

        if (null === $this->header) {
            if (!\array_key_exists(0, $this->firstRawLines)) {
                throw new CsvReadException('No header row found');
            }
            $header = [];
            foreach (str_getcsv($this->firstRawLines[0], $this->delimiter, $this->enclosure, $this->escape) as $cell) {
                if (null === $cell || '' === $cell) {
                    throw new CsvReadException('Empty header row is not supported');
                }
                $header[] = $cell;
            }

            $this->header = $header;
        }
    }

    /**
     * @return non-empty-string
     */
    private function guessDelimiter(): string
    {
        foreach ($this->firstRawLines ?? [] as $line) {
            foreach (self::DELIMITER_COMMON as $delimiter) {
                if (str_contains($line, $delimiter)) {
                    return $delimiter;
                }
            }
        }

        return self::DELIMITER_DEFAULT;
    }

    /**
     * @return non-empty-string
     */
    private function guessEnclosure(): string
    {
        foreach ($this->firstRawLines ?? [] as $line) {
            foreach (self::ENCLOSURE_COMMON as $enclosure) {
                $enclosureCount = substr_count($line, $enclosure);
                if (0 !== $enclosureCount && 0 === $enclosureCount % 2) {
                    return $enclosure;
                }
            }
        }

        return self::ENCLOSURE_DEFAULT;
    }

    /**
     * @param int<1, max> $linesToExtract
     *
     * @phpstan-assert !null $this->firstRawLines
     */
    private function extractFirstLines(int $linesToExtract): void
    {
        if (null !== $this->firstRawLines) {
            return;
        }

        $this->firstRawLines = [];
        for ($i = 0; $i < $linesToExtract; ++$i) {
            $line = fgets($this->resource);
            if (false === $line) {
                if (feof($this->resource)) {
                    break;
                }
                throw new CsvReadException('Line could not be read');
            }

            if (0 === $i) {
                foreach (self::BOMS as $bom) {
                    if (str_starts_with($line, $bom)) {
                        $line = substr($line, \strlen($bom));
                        break;
                    }
                }
            }

            // We make sure that the line is properly encoded in UTF-8, given the (optional) charset
            $this->firstRawLines[] = (new ByteString($line))->toCodePointString($this->charset)->toString();
        }
    }

    /**
     * @return list<string>
     */
    private function buildRow(int $rowNumber, string $line, string $delimiter, string $enclosure): array
    {
        $row = [];
        foreach (str_getcsv($line, $delimiter, $enclosure, $this->escape) as $rowCell) {
            if (null === $rowCell) {
                throw new CsvReadException("Row #{$rowNumber} is empty");
            }
            $row[] = $rowCell;
        }

        return $row;
    }
}
