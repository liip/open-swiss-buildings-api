<?php

declare(strict_types=1);

namespace App\Infrastructure\Address;

use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;

final readonly class StreetFactory
{
    private const string REGEXP_NUMBER = '(?:(?:(?P<number>\d+)(?P<suffix1>(?:(?:[a-z]|\s[a-z])?(?:[\._]?[a-z]?\d+)?)))|(?P<suffix2>[\._][a-z]?\d+))';

    private function __construct() {}

    /**
     * @param non-empty-string|null $streetName
     * @param non-empty-string|null $streetNumber
     */
    public static function createFromSeparateStrings(?string $streetName, ?string $streetNumber = null): Street
    {
        return new Street($streetName, null !== $streetNumber ? self::createNumberFromString($streetNumber) : null);
    }

    /**
     * @param non-empty-string $streetNumber
     */
    public static function createNumberFromString(string $streetNumber): ?StreetNumber
    {
        if (1 === preg_match('/^' . self::REGEXP_NUMBER . '$/i', $streetNumber, $matches)) {
            $suffix = trim($matches['suffix1']);
            if ('' === $suffix && \array_key_exists('suffix2', $matches)) {
                $suffix = trim($matches['suffix2']);
            }
            $suffix = '' !== $suffix ? $suffix : null;
            if ('' !== $matches['number']) {
                $number = (int) $matches['number'];
                if (0 === $number) {
                    $number = null;
                }
                if ($number < 0) {
                    throw new \LogicException("Match for house number must be a positive integer, but it is \"{$number}\"");
                }

                if (null === $number && null === $suffix) {
                    return null;
                }

                return new StreetNumber($number, $suffix);
            }

            return new StreetNumber(null, $suffix);
        }

        return new StreetNumber(null, $streetNumber);
    }
}
