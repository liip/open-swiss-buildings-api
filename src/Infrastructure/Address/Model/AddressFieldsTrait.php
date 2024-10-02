<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

use Doctrine\ORM\Mapping as ORM;

trait AddressFieldsTrait
{
    #[ORM\Column(length: 60)]
    public string $streetName;

    /**
     * Street name without special characters, used for matching.
     */
    #[ORM\Column(length: 60)]
    public string $streetNameNormalized;

    #[ORM\Column()]
    public int $streetHouseNumber;

    #[ORM\Column(length: 10)]
    public string $streetHouseNumberSuffix;

    /**
     * House number suffix without special characters, used for matching.
     */
    #[ORM\Column(length: 14)]
    public string $streetHouseNumberSuffixNormalized;

    #[ORM\Column(length: 4)]
    public string $postalCode;

    #[ORM\Column(length: 60)]
    public string $locality;

    /**
     * Locality without special characters, used for matching.
     */
    #[ORM\Column(length: 60)]
    public string $localityNormalized;

    public function getStreetHouseNumber(): string
    {
        if (0 === $this->streetHouseNumber) {
            return '';
        }

        return "{$this->streetHouseNumber}{$this->streetHouseNumberSuffix}";
    }

    public function getStreet(): string
    {
        $number = $this->getStreetHouseNumber();
        if ('' !== $number) {
            $number = " {$number}";
        }

        return "{$this->streetName}{$number}";
    }

    public function getAddress(): string
    {
        return "{$this->getStreet()}, {$this->postalCode} {$this->locality}";
    }
}
