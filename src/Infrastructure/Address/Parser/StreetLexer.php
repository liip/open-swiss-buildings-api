<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Parser;

use Composer\Pcre\MatchResult;
use Composer\Pcre\MatchStrictGroupsResult;
use Composer\Pcre\Regex;
use Doctrine\Common\Lexer\AbstractLexer;

/**
 * @extends AbstractLexer<StreetTokenEnum, string>
 */
final class StreetLexer extends AbstractLexer
{
    /** @var string Matching sequences of numbers, those might appear as both the part of a street name, or the house-number */
    private const string REGEXP_NUMBER = '\d+';

    /** @var string Matching alpha-numeric strings representing street names, we use UTF-8 regexp groups to handle umlauts and accents */
    private const string REGEXP_STREET_NAME = '[\p{L}\p{M}\']+';

    /** @var string Matching house numbers: this is used used for tokenization in the Lexing process */
    private const string REGEXP_HOUSE_NUMBER = '\d+(?:\s?[a-z]{1})?(?:\s?\.[a-z]?\d+)?$';

    /** @var string Matching house numbers, with named capturing-groups: used to identify the parts of the street number */
    private const string CAPTURING_REGEXP_HOUSE_NUMBER = '^(?P<houseNumber>\d+)?(?P<houseNumberSuffix>(\s?[a-z]{1})?(?:\s?\.[a-z]?\d+)?)$';

    /** @var string Matching of ranges of street numbers (in the form "12-15"): used for tokenization in the Lexing process */
    private const string REGEXP_HOUSE_NUMBER_RANGE = '\d+\s*-\s*\d+$';

    /** @var string Matching of ranges of street numbers, with named capturing-groups: used to extract the number range */
    private const string CAPTURING_REGEXP_HOUSE_NUMBER_RANGE = '(?P<houseNumberFrom>\d+)\s*-\s*(?P<houseNumberTo>\d+)$';

    /** @var string Matching of ranges of street number suffixes (in the form "12 a - c"): used for tokenization in the Lexing process */
    private const string REGEXP_HOUSE_NUMBER_SUFFIX_RANGE = '\d+\s*[a-z]\s*-\s*[a-z]$';

    /** @var string Matching of ranges of street number suffixes, with named capturing-groups: used extract the number and suffix range */
    private const string CAPTURING_REGEXP_HOUSE_NUMBER_SUFFIX_RANGE = '(?P<houseNumber>\d+)\s*(?P<rangeSuffixFrom>[a-z])\s*-\s*(?P<rangeSuffixTo>[a-z])$';

    public function __construct(string $input)
    {
        $this->setInput($input);
    }

    /**
     * @return array{from: int, to: int}|null
     */
    public static function parseNumberRange(string $input): ?array
    {
        $matches = self::matchStrictGroups(self::CAPTURING_REGEXP_HOUSE_NUMBER_RANGE, $input);
        if (!$matches->matched) {
            return null;
        }

        return [
            'from' => (int) $matches->matches['houseNumberFrom'],
            'to' => (int) $matches->matches['houseNumberTo'],
        ];
    }

    /**
     * @return array{number: int|null, suffix: string|null}|null
     */
    public static function parseNumber(string $input): ?array
    {
        $matches = self::matchGroups(self::CAPTURING_REGEXP_HOUSE_NUMBER, $input);
        if ([] === array_filter($matches->matches)) {
            return null;
        }

        $number = $matches->matches['houseNumber'] ?? null;
        $suffix = $matches->matches['houseNumberSuffix'] ?? null;

        return [
            'number' => null !== $number ? (int) $number : null,
            'suffix' => $suffix ?: null,
        ];
    }

    /**
     * @return array{number: int, from: string, to: string}|null
     */
    public static function parseNumberSuffixRange(string $input): ?array
    {
        $matches = self::matchStrictGroups(self::CAPTURING_REGEXP_HOUSE_NUMBER_SUFFIX_RANGE, $input);
        if (!$matches->matched) {
            return null;
        }

        return [
            'number' => (int) $matches->matches['houseNumber'],
            'from' => trim($matches->matches['rangeSuffixFrom']),
            'to' => trim($matches->matches['rangeSuffixTo']),
        ];
    }

    protected function getCatchablePatterns(): array
    {
        // Order is important here, as the first patter matching wins!
        return [
            self::REGEXP_HOUSE_NUMBER_SUFFIX_RANGE,
            self::REGEXP_HOUSE_NUMBER_RANGE,
            self::REGEXP_HOUSE_NUMBER,
            self::REGEXP_STREET_NAME,
            self::REGEXP_NUMBER,
        ];
    }

    protected function getNonCatchablePatterns(): array
    {
        return ['\s+', '(.)'];
    }

    protected function getType(string &$value): StreetTokenEnum
    {
        // dump($value);
        if (is_numeric($value)) {
            return StreetTokenEnum::T_NUMBER;
        }
        if ('.' === $value) {
            return StreetTokenEnum::T_DOT;
        }
        if ($this->isMatch(self::REGEXP_HOUSE_NUMBER_SUFFIX_RANGE, $value)) {
            return StreetTokenEnum::T_HOUSE_NUMBER_SUFFIX_RANGE;
        }
        if ($this->isMatch(self::REGEXP_HOUSE_NUMBER_RANGE, $value)) {
            return StreetTokenEnum::T_HOUSE_NUMBER_RANGE;
        }

        if ($this->isMatch(self::REGEXP_HOUSE_NUMBER, $value)) {
            return StreetTokenEnum::T_HOUSE_NUMBER;
        }

        if ($this->isMatch(self::REGEXP_STREET_NAME, $value)) {
            return StreetTokenEnum::T_STREET_NAME;
        }

        return StreetTokenEnum::T_UNKNOWN;
    }

    private function isMatch(string $pattern, string $value): bool
    {
        return Regex::isMatch("|^{$pattern}$|ui", $value);
    }

    private static function matchGroups(string $pattern, string $input): MatchResult
    {
        return Regex::match("|{$pattern}$|ui", $input);
    }

    private static function matchStrictGroups(string $pattern, string $input): MatchStrictGroupsResult
    {
        // @phpstan-ignore-next-line
        return Regex::matchStrictGroups("|{$pattern}$|ui", $input);
    }
}
