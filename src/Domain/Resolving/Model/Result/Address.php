<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Result;

use OpenApi\Attributes as OA;

final readonly class Address implements \Stringable, \JsonSerializable
{
    public function __construct(
        #[OA\Property(property: 'municipality_code')]
        public string $municipalityCode,
        #[OA\Property(property: 'postal_code')]
        public string $postalCode,
        public string $locality,
        #[OA\Property(property: 'street_name')]
        public string $streetName,
        #[OA\Property(property: 'street_house_number')]
        public string $streetHouseNumber,
        #[OA\Property(property: 'country_code')]
        public string $countryCode,
    ) {}

    public function __toString(): string
    {
        return "{$this->streetName} {$this->streetHouseNumber}, {$this->postalCode} {$this->locality} - {$this->countryCode}";
    }

    /**
     * @return array{municipality_code: string, postal_code: string, locality: string, street_name: string, street_house_number: string, country_code: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'municipality_code' => $this->municipalityCode,
            'postal_code' => $this->postalCode,
            'locality' => $this->locality,
            'street_name' => $this->streetName,
            'street_house_number' => $this->streetHouseNumber,
            'country_code' => $this->countryCode,
        ];
    }
}
