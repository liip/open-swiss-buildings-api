<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

final readonly class Street implements \Stringable
{
    public function __construct(
        /**
         * @var non-empty-string|null
         */
        public ?string $streetName,
        public ?StreetNumberInterface $number = null,
    ) {
        if (null === $this->streetName && null === $this->number) {
            throw new \InvalidArgumentException('One of street name or number needs to be set');
        }
    }

    /**
     * @param non-empty-string|null $streetName
     */
    public static function createOptional(?string $streetName, ?StreetNumber $number = null): ?self
    {
        if (null === $streetName && null === $number) {
            return null;
        }

        return new self($streetName, $number);
    }

    public function withHouseNumber(StreetNumberInterface $houseNumber): self
    {
        return new self($this->streetName, $houseNumber);
    }

    public function __toString(): string
    {
        $number = null !== $this->number ? " {$this->number}" : '';

        return "{$this->streetName}{$number}";
    }

    public function equalsTo(self $other): bool
    {
        if ($other->streetName !== $this->streetName) {
            return false;
        }

        if (null === $other->number && null !== $this->number) {
            return false;
        }
        if (null === $this->number && null !== $other->number) {
            return false;
        }

        if (null !== $this->number && null !== $other->number && !$this->number->equalsTo($other->number)) {
            return false;
        }

        return true;
    }
}
