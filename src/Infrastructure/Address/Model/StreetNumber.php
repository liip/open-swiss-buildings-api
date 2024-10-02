<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

final readonly class StreetNumber implements StreetNumberInterface
{
    public function __construct(
        /**
         * @var positive-int|null
         */
        public ?int $number,
        /**
         * @var non-empty-string|null
         */
        public ?string $suffix = null,
    ) {
        if (null === $this->number && null === $this->suffix) {
            throw new \InvalidArgumentException('One of house number or suffix needs to be set');
        }
    }

    /**
     * @param positive-int|null     $number
     * @param non-empty-string|null $suffix
     */
    public static function createOptional(?int $number, ?string $suffix = null): ?self
    {
        if (null === $number && null === $suffix) {
            return null;
        }

        return new self($number, $suffix);
    }

    public function withoutSuffix(): string
    {
        return (string) $this->number;
    }

    public function __toString(): string
    {
        return "{$this->number}{$this->suffix}";
    }

    public function equalsTo(StreetNumberInterface $other): bool
    {
        return ($other instanceof self)
            && $other->number === $this->number
            && $other->suffix === $this->suffix;
    }
}
