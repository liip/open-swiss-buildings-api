<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

final readonly class StreetNumberRange implements \Stringable, StreetNumberInterface
{
    /**
     * @param positive-int|null $numberFrom
     * @param positive-int|null $numberTo
     */
    public static function buildOptional(?int $numberFrom, ?int $numberTo): ?self
    {
        if (null === $numberFrom || null === $numberTo) {
            return null;
        }

        if ($numberFrom > $numberTo) {
            throw new \InvalidArgumentException("Street number range must be from MIN to MAX values, [{$numberFrom},{$numberTo}] given");
        }

        return new self($numberFrom, $numberTo);
    }

    public function __construct(
        /**
         * @var positive-int
         */
        public int $numberFrom,

        /**
         * @var positive-int
         */
        public int $numberTo,
    ) {}

    public function __toString(): string
    {
        return "[{$this->numberFrom}{$this->numberTo}]";
    }

    public function equalsTo(StreetNumberInterface $other): bool
    {
        return
            $other instanceof self
            && $other->numberFrom === $this->numberFrom
            && $other->numberTo === $this->numberTo;
    }
}
