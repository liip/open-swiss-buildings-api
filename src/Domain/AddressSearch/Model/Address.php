<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

use App\Infrastructure\Serialization\Decoder;

/**
 * @phpstan-type AddressAsArray array{
 *    streetName: string,
 *    streetNameAbbreviation: string,
 *    streetHouseNumber: string,
 *    postalCode: string,
 *    locality: string,
 *    municipality: string,
 *    municipalityCode: string,
 *    countryCode: string,
 *  }
 */
final class Address implements \JsonSerializable
{
    public function __construct(
        public string $streetName,
        public string $streetNameAbbreviation,
        public string $streetHouseNumber,
        public string $postalCode,
        public string $locality,
        public string $municipality,
        public string $municipalityCode,
        public string $countryCode,
    ) {}

    /**
     * @param array<string|int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            streetName: Decoder::readString($data, 'streetName'),
            streetNameAbbreviation: Decoder::readString($data, 'streetNameAbbreviation'),
            streetHouseNumber: Decoder::readString($data, 'streetHouseNumber'),
            postalCode: Decoder::readString($data, 'postalCode'),
            locality: Decoder::readString($data, 'locality'),
            municipality: Decoder::readString($data, 'municipality'),
            municipalityCode: Decoder::readString($data, 'municipalityCode'),
            countryCode: Decoder::readString($data, 'countryCode'),
        );
    }

    public function formatForSearch(bool $fullStreetName = true): string
    {
        return trim(implode(' ', [
            $fullStreetName ? $this->streetName : $this->streetNameAbbreviation,
            $this->streetHouseNumber,
            $this->postalCode,
            $this->locality,
        ]));
    }

    /**
     * @return AddressAsArray
     */
    public function jsonSerialize(): array
    {
        return [
            'streetName' => $this->streetName,
            'streetNameAbbreviation' => $this->streetNameAbbreviation,
            'streetHouseNumber' => $this->streetHouseNumber,
            'postalCode' => $this->postalCode,
            'locality' => $this->locality,
            'municipality' => $this->municipality,
            'municipalityCode' => $this->municipalityCode,
            'countryCode' => $this->countryCode,
        ];
    }
}
