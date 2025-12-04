<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

final readonly class StreetNumberSuffixRange implements \Stringable, StreetNumberInterface
{
    /**
     * @param positive-int|null $number
     */
    public static function buildOptional(?int $number, ?string $suffixFrom, ?string $suffixTo): ?self
    {
        if (null === $number || null === $suffixFrom || '' === $suffixFrom || null === $suffixTo || '' === $suffixTo) {
            return null;
        }

        return new self($number, $suffixFrom, $suffixTo);
    }

    public function __construct(
        /**
         * @var positive-int
         */
        public int $number,

        /**
         * @var non-empty-string
         */
        public string $suffixFrom,

        /**
         * @var non-empty-string
         */
        public string $suffixTo,
    ) {}

    public function __toString(): string
    {
        return "[{$this->number}[{$this->suffixFrom}-{$this->suffixTo}]";
    }

    public function equalsTo(StreetNumberInterface $other): bool
    {
        return
            $other instanceof self
            && $other->number === $this->number
            && $other->suffixFrom === $this->suffixFrom
            && $other->suffixTo === $this->suffixTo;
    }
}
