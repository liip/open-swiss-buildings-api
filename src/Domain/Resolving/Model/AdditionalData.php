<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model;

/**
 * This represents a list of additional data/columns.
 *
 * Internally, this is a list of additional data, which
 * has multiple entries in case of duplicate entries.
 * The list of additional data is merged for output.
 *
 * @phpstan-type AdditionalDataAsArray array<string, string>
 */
final class AdditionalData implements \JsonSerializable
{
    public const string ADDITIONAL_DATA_SEPARATOR = '||';

    private const string DATA_ADDRESS_STREET = 'data_address_street';
    private const string DATA_ADDRESS_POSTAL_CODE = 'data_address_postal_code';
    private const string DATA_ADDRESS_LOCALITY = 'data_address_postal_locality';

    private const array ADDRESS_DATA = [
        self::DATA_ADDRESS_STREET,
        self::DATA_ADDRESS_POSTAL_CODE,
        self::DATA_ADDRESS_LOCALITY,
    ];

    private const array INTERNAL_DATA = [
        self::DATA_ADDRESS_STREET,
        self::DATA_ADDRESS_POSTAL_CODE,
        self::DATA_ADDRESS_LOCALITY,
    ];

    /**
     * @var AdditionalDataAsArray|null
     */
    private ?array $mergedList = null;

    private function __construct(
        /**
         * A list of additional data/columns.
         *
         * @var list<AdditionalDataAsArray>
         */
        private array $additionalData,
    ) {}

    /**
     * @param AdditionalDataAsArray $additionalData additional data/columns
     */
    public static function create(array $additionalData): self
    {
        return new self([$additionalData]);
    }

    public function withAddress(string $street, string $postalCode, string $locality): void
    {
        if ([[]] === $this->additionalData) {
            $this->additionalData[] = [];
        }

        $this->additionalData[0][self::DATA_ADDRESS_STREET] = $street;
        $this->additionalData[0][self::DATA_ADDRESS_POSTAL_CODE] = $postalCode;
        $this->additionalData[0][self::DATA_ADDRESS_LOCALITY] = $locality;
    }

    /**
     * @param list<AdditionalDataAsArray> $additionalData a list of additional data/columns
     */
    public static function createFromList(array $additionalData): self
    {
        return new self($additionalData);
    }

    public function isEmpty(): bool
    {
        return [[]] === $this->additionalData;
    }

    /**
     * @return list<AdditionalDataAsArray>
     */
    public function getAsList(): array
    {
        return $this->additionalData;
    }

    /**
     * @param non-empty-string $name
     */
    public function get(string $name): ?string
    {
        $data = $this->computeMergedLists();

        return $data[$name] ?? null;
    }

    /**
     * @return array{street: string, postalCode: string, locality: string}
     */
    public function getAddressData(): array
    {
        $merged = $this->computeMergedLists();

        return [
            'street' => $merged[self::DATA_ADDRESS_STREET] ?? '',
            'postalCode' => $merged[self::DATA_ADDRESS_POSTAL_CODE] ?? '',
            'locality' => $merged[self::DATA_ADDRESS_LOCALITY] ?? '',
        ];
    }

    /**
     * @return non-empty-string|null
     */
    public function getAddressString(): ?string
    {
        $data = $this->getDataWithInternal();

        $street = $data[self::DATA_ADDRESS_STREET] ?? null;
        $postalCode = $data[self::DATA_ADDRESS_POSTAL_CODE] ?? null;
        $locality = $data[self::DATA_ADDRESS_LOCALITY] ?? null;

        if (\in_array(null, [$street, $postalCode, $locality], true)) {
            return null;
        }

        return "{$street}, {$postalCode} {$locality}";
    }

    /**
     * @return AdditionalDataAsArray
     */
    public function getData(): array
    {
        return array_filter(
            $this->computeMergedLists(),
            static fn(string $name): bool => !\in_array($name, self::INTERNAL_DATA, true),
            \ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return AdditionalDataAsArray
     */
    public function getDataWithInternal(): array
    {
        return $this->computeMergedLists();
    }

    /**
     * @return AdditionalDataAsArray
     */
    public function jsonSerialize(): array
    {
        return $this->getDataWithInternal();
    }

    /**
     * @return AdditionalDataAsArray
     */
    private function computeMergedLists(): array
    {
        if (null !== $this->mergedList) {
            return $this->mergedList;
        }

        if ($this->isEmpty()) {
            return $this->mergedList = [];
        }

        $data = [];
        foreach ($this->additionalData as $additionalData) {
            foreach ($additionalData as $key => $value) {
                if (!\array_key_exists($key, $data)) {
                    $data[$key] = [];

                    if (\in_array($key, self::ADDRESS_DATA, true)) {
                        // Keep address data as a single value
                        $data[$key] = [$value];
                        continue;
                    }
                }
                if (!\in_array($value, $data[$key], true)) {
                    $data[$key][] = $value;
                }
            }
        }

        $this->mergedList = array_map(
            static fn(array $values): string => implode(self::ADDITIONAL_DATA_SEPARATOR, $values),
            $data,
        );

        uksort($this->mergedList, static fn(mixed $a, mixed $b): int => strnatcmp($a, $b));

        return $this->mergedList;
    }
}
