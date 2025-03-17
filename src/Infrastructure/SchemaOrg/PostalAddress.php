<?php

declare(strict_types=1);

namespace App\Infrastructure\SchemaOrg;

final readonly class PostalAddress implements \Stringable
{
    public function __construct(
        /**
         * The country, in 2-letter ISO 3166-1 alpha-2 format, for example "CH".
         */
        public string $addressCountry,
        /**
         * The locality in which the street address is, and which is in the region.
         */
        public string $addressLocality,
        /**
         * The region in which the locality is, and which is in the country.
         */
        public string $addressRegion,
        /**
         * The postal code. For example, 8005.
         */
        public string $postalCode,
        /**
         * The address of the item.
         */
        public string $streetAddress,
        /**
         * The language of the item.
         */
        public string $inLanguage,
    ) {}

    public function __toString(): string
    {
        return "{$this->streetAddress}, {$this->postalCode} {$this->addressLocality} - {$this->addressCountry}";
    }
}
