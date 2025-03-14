<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use OpenApi\Attributes as OA;

/**
 * This represents the metadata of a resolver job.
 *
 * Metadata can be used to pass some information to
 * the resolver job, which is available during resolving.
 *
 * @phpstan-type ResolverMetadataAsArray array{
 *     additional-columns?: string,
 *     geojson-srid?: non-negative-int,
 *     charset?: non-empty-string,
 *     csv-delimiter?: non-empty-string,
 *     csv-enclosure?: non-empty-string,
 * }
 */
final readonly class ResolverMetadata implements \JsonSerializable
{
    private const string ADDITIONAL_COLUMNS_DELIMITER = '!!';

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    #[OA\Property(property: 'additional_columns')]
    public ?array $additionalColumns;

    /**
     * @param non-empty-list<non-empty-string>|null $additionalColumns
     */
    public function __construct(
        ?array $additionalColumns = null,
        /**
         * @phpstan-var non-negative-int|null
         */
        #[OA\Property(property: 'geojson_srid')]
        public ?int $geoJsonSRID = null,
        /**
         * @var non-empty-string|null
         */
        #[OA\Property(property: 'charset')]
        public ?string $charset = null,
        /**
         * @var non-empty-string|null
         */
        #[OA\Property(property: 'csv_delimiter')]
        public ?string $csvDelimiter = null,
        /**
         * @var non-empty-string|null
         */
        #[OA\Property(property: 'csv_enclosure')]
        public ?string $csvEnclosure = null,
    ) {
        if (null !== $this->csvDelimiter && 1 !== \strlen($this->csvDelimiter)) {
            throw new \InvalidArgumentException('CSV delimiter needs to be one single-byte character');
        }
        if (null !== $this->csvEnclosure && 1 !== \strlen($this->csvEnclosure)) {
            throw new \InvalidArgumentException('CSV enclosure needs to be one single-byte character');
        }

        $this->additionalColumns = null !== $additionalColumns ? $this->sortAdditionalColumns($additionalColumns) : null;
    }

    /**
     * @param array{
     *     additional-columns?: string|null,
     *     geojson-srid?: non-negative-int|null,
     *     charset?: non-empty-string|null,
     *     csv-delimiter?: non-empty-string|null,
     *     csv-enclosure?: non-empty-string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $additionalColumns = null;
        if (\array_key_exists('additional-columns', $data)) {
            $additionalColumns = [];
            if (!\is_string($data['additional-columns'])) {
                throw new \InvalidArgumentException('Additional column must be a string');
            }

            foreach (explode(self::ADDITIONAL_COLUMNS_DELIMITER, $data['additional-columns']) as $column) {
                if ('' === $column) {
                    throw new \InvalidArgumentException('Additional column cannot be empty');
                }
                $additionalColumns[] = $column;
            }
        }

        return new self(
            additionalColumns: $additionalColumns,
            geoJsonSRID: $data['geojson-srid'] ?? null,
            charset: $data['charset'] ?? null,
            csvDelimiter: $data['csv-delimiter'] ?? null,
            csvEnclosure: $data['csv-enclosure'] ?? null,
        );
    }

    /**
     * @param non-empty-list<non-empty-string> $additionalColumns
     */
    public function withAdditionalColumns(array $additionalColumns): self
    {
        return new self(
            additionalColumns: $additionalColumns,
            geoJsonSRID: $this->geoJsonSRID,
            charset: $this->charset,
            csvDelimiter: $this->csvDelimiter,
            csvEnclosure: $this->csvEnclosure,
        );
    }

    /**
     * @param non-empty-string $csvDelimiter
     */
    public function withCsvDelimiter(string $csvDelimiter): self
    {
        return new self(
            additionalColumns: $this->additionalColumns,
            geoJsonSRID: $this->geoJsonSRID,
            charset: $this->charset,
            csvDelimiter: $csvDelimiter,
            csvEnclosure: $this->csvEnclosure,
        );
    }

    /**
     * @param non-empty-string $csvEnclosure
     */
    public function withCsvEnclosure(string $csvEnclosure): self
    {
        return new self(
            additionalColumns: $this->additionalColumns,
            geoJsonSRID: $this->geoJsonSRID,
            charset: $this->charset,
            csvDelimiter: $this->csvDelimiter,
            csvEnclosure: $csvEnclosure,
        );
    }

    /**
     * @param non-empty-string $charset
     */
    public function withCharset(string $charset): self
    {
        return new self(
            additionalColumns: $this->additionalColumns,
            geoJsonSRID: $this->geoJsonSRID,
            charset: $charset,
            csvDelimiter: $this->csvDelimiter,
            csvEnclosure: $this->csvEnclosure,
        );
    }

    /**
     * @param non-negative-int $geoJsonSRID
     */
    public function withGeoJsonSRID(int $geoJsonSRID): self
    {
        return new self(
            additionalColumns: $this->additionalColumns,
            geoJsonSRID: $geoJsonSRID,
            charset: $this->charset,
            csvDelimiter: $this->csvDelimiter,
            csvEnclosure: $this->csvEnclosure,
        );
    }

    /**
     * @return ResolverMetadataAsArray
     */
    public function toArray(): array
    {
        $data = [];
        if (null !== $this->additionalColumns) {
            $data['additional-columns'] = implode(self::ADDITIONAL_COLUMNS_DELIMITER, $this->additionalColumns);
        }
        if (null !== $this->geoJsonSRID) {
            $data['geojson-srid'] = $this->geoJsonSRID;
        }
        if (null !== $this->charset) {
            $data['charset'] = $this->charset;
        }
        if (null !== $this->csvDelimiter) {
            $data['csv-delimiter'] = $this->csvDelimiter;
        }
        if (null !== $this->csvEnclosure) {
            $data['csv-enclosure'] = $this->csvEnclosure;
        }

        return $data;
    }

    /**
     * @return array{
     *     additional_columns?: string,
     *     geojson_srid?: non-negative-int,
     *     charset?: non-empty-string,
     *     csv_delimiter?: non-empty-string,
     *     csv_enclosure?: non-empty-string
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [];
        if (null !== $this->additionalColumns) {
            $data['additional_columns'] = implode(self::ADDITIONAL_COLUMNS_DELIMITER, $this->additionalColumns);
        }
        if (null !== $this->geoJsonSRID) {
            $data['geojson_srid'] = $this->geoJsonSRID;
        }
        if (null !== $this->charset) {
            $data['charset'] = $this->charset;
        }
        if (null !== $this->csvDelimiter) {
            $data['csv_delimiter'] = $this->csvDelimiter;
        }
        if (null !== $this->csvEnclosure) {
            $data['csv_enclosure'] = $this->csvEnclosure;
        }

        return $data;
    }

    /**
     * @param non-empty-list<non-empty-string> $additionalColumns
     *
     * @return non-empty-list<non-empty-string>
     */
    private function sortAdditionalColumns(array $additionalColumns): array
    {
        sort($additionalColumns, \SORT_NATURAL);

        return $additionalColumns;
    }
}
