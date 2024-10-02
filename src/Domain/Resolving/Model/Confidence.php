<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model;

final readonly class Confidence implements \Stringable
{
    private function __construct(
        /**
         * @var int<0, 100>
         */
        private int $valueAsInt,
    ) {}

    public static function fromInt(int $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new \UnexpectedValueException("Confidence as integer needs to be between 0 and 100, but {$value} was given");
        }

        return new self($value);
    }

    public function asFloat(): float
    {
        return $this->valueAsInt / 100;
    }

    public function __toString(): string
    {
        return (string) $this->asFloat();
    }
}
