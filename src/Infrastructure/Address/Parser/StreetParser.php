<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Parser;

use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Address\Model\StreetNumberRange;
use App\Infrastructure\Address\Model\StreetNumberSuffixRange;
use Doctrine\Common\Lexer\Token;

final readonly class StreetParser
{
    private function __construct() {}

    /**
     * @param non-empty-string $input
     *
     * @throws \InvalidArgumentException if the street is not valid
     */
    public static function createStreetFromString(string $input): Street
    {
        $lexer = new StreetLexer($input);
        // Two next() are required to get first token's value
        $lexer->moveNext();
        $lexer->moveNext();

        $streetName = null;
        $streetNumber = null;

        while (true) {
            $token = $lexer->token;
            if (!$token instanceof Token) {
                break;
            }

            switch ($token->type) {
                case StreetTokenEnum::T_UNKNOWN:
                case StreetTokenEnum::T_STREET_NAME:
                    $streetName .= ' ' . $token->value;
                    break;
                case StreetTokenEnum::T_DOT:
                    // If the next token is empty, we stop and ignore the dot
                    $lookahead = $lexer->lookahead;
                    if (!$lookahead instanceof Token) {
                        break;
                    }
                    // If the next token is a number, and the next is the end of line
                    // we're parsing a `.99` house-number case: we merge the two tokens and build a number
                    if ($lookahead->isA(StreetTokenEnum::T_NUMBER) && null === $lexer->glimpse()) {
                        $streetNumber = self::parseHouseNumber($token->value . $lookahead->value);
                        break 2;
                    }
                    break;

                case StreetTokenEnum::T_NUMBER:
                    // If we have a number and it is at the end of the string: we threat it as the house number
                    if (!$lexer->lookahead instanceof Token) {
                        $streetNumber = self::parseHouseNumber($token->value);
                        break 2;
                    }

                    // .. otherwise the number is part of the street name
                    $streetName .= ' ' . $token->value;
                    break;

                case StreetTokenEnum::T_HOUSE_NUMBER:
                    $streetNumber = self::parseHouseNumber($token->value);
                    break;
                case StreetTokenEnum::T_HOUSE_NUMBER_RANGE:
                    $streetNumber = self::parseHouseNumberRange($token->value);
                    break;
                case StreetTokenEnum::T_HOUSE_NUMBER_SUFFIX_RANGE:
                    $streetNumber = self::parseHouseNumberSuffixRange($token->value);
                    break;
            }

            $lexer->moveNext();
        }

        $streetName = null !== $streetName ? trim($streetName) : null;

        return new Street($streetName ?: null, $streetNumber);
    }

    private static function parseHouseNumber(string $value): ?StreetNumber
    {
        if (null === $number = StreetLexer::parseNumber($value)) {
            return null;
        }

        // Avoid getting non-positive numbers
        $houseNumber = $number['number'] > 0 ? $number['number'] : null;
        $suffix = $number['suffix'] ? trim($number['suffix']) : null;

        return StreetNumber::createOptional($houseNumber, $suffix ?: null);
    }

    private static function parseHouseNumberRange(string $value): ?StreetNumberRange
    {
        if (null === $numberRange = StreetLexer::parseNumberRange($value)) {
            return null;
        }

        $from = $numberRange['from'] > 0 ? $numberRange['from'] : null;
        $to = $numberRange['to'] > 0 ? $numberRange['to'] : null;

        return StreetNumberRange::buildOptional($from, $to);
    }

    private static function parseHouseNumberSuffixRange(string $value): ?StreetNumberSuffixRange
    {
        if (null === $suffixRange = StreetLexer::parseNumberSuffixRange($value)) {
            return null;
        }

        $number = $suffixRange['number'] > 0 ? $suffixRange['number'] : null;

        return StreetNumberSuffixRange::buildOptional($number, $suffixRange['from'], $suffixRange['to']);
    }
}
