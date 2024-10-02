<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model;

final readonly class CsvRow
{
    public function __construct(
        public int $number,
        /**
         * @var array<non-empty-string, string>
         */
        public array $data,
    ) {}

    /**
     * @param list<string>           $rowData
     * @param list<non-empty-string> $header
     */
    public static function fromCsv(int $number, array $rowData, array $header): self
    {
        try {
            $data = array_combine($header, $rowData);
        } catch (\ValueError) {
            $headerCount = \count($header);
            throw new \InvalidArgumentException("Row #{$number} does not match expected column count of {$headerCount}!");
        }

        return new self($number, $data);
    }

    public function get(string $name): string
    {
        if (!\array_key_exists($name, $this->data)) {
            throw new \InvalidArgumentException("Row doesn't have data for \"{$name}\"");
        }

        return $this->data[$name];
    }
}
