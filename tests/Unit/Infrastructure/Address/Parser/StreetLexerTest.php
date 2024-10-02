<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Address\Parser;

use App\Infrastructure\Address\Parser\StreetLexer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class StreetLexerTest extends TestCase
{
    /**
     * @return iterable<array{array{number: ?int, suffix: ?string}|null, string}>
     */
    public static function provideParseNumberCases(): iterable
    {
        yield [null, ''];
        yield [null, 'Main Steet'];
        yield [['number' => 12, 'suffix' => null], '12'];
        yield [['number' => 12, 'suffix' => 'a'], '12a'];
        yield [['number' => 12, 'suffix' => 'a.1'], '12a.1'];
        yield [['number' => 12, 'suffix' => 'a.b15'], '12a.b15'];
        yield [['number' => null, 'suffix' => 'a'], 'a'];
        yield [['number' => null, 'suffix' => '.2'], '.2'];
    }

    /**
     * @param array{number: ?int, suffix: ?string}|null $expected
     */
    #[DataProvider('provideParseNumberCases')]
    public function testParseNumber(?array $expected, string $value): void
    {
        $this->assertSame($expected, StreetLexer::parseNumber($value));
    }

    /**
     * @return iterable<array{array{from: int, to: int}|null, string}>
     */
    public static function provideParseNumberRangesCases(): iterable
    {
        yield [null, ''];
        yield [null, 'Main Steet'];
        yield [['from' => 12, 'to' => 15], '12-15'];
        yield [['from' => 12, 'to' => 15], '12 - 15'];
        yield [['from' => 12, 'to' => 15], '12- 15'];
        yield [['from' => 12, 'to' => 15], '12 -15'];
    }

    /**
     * @param array{from: int, to: int}|null $expected
     */
    #[DataProvider('provideParseNumberRangesCases')]
    public function testParseNumberRanges(?array $expected, string $value): void
    {
        $this->assertSame($expected, StreetLexer::parseNumberRange($value));
    }

    /**
     * @return iterable<array{array{number: int, from: string, to: string}|null, string}>
     */
    public static function provideParseNumberSuffixRangesCases(): iterable
    {
        yield [null, ''];
        yield [null, 'Main Steet'];
        yield [['number' => 12, 'from' => 'a', 'to' => 'c'], '12a-c'];
        yield [['number' => 12, 'from' => 'a', 'to' => 'c'], '12a - c'];
        yield [['number' => 12, 'from' => 'a', 'to' => 'c'], '12a- c'];
        yield [['number' => 12, 'from' => 'a', 'to' => 'c'], '12a -c'];
    }

    /**
     * @param array{number: string, from: string, to: string}|null $expected
     */
    #[DataProvider('provideParseNumberSuffixRangesCases')]
    public function testParseNumberSuffixRanges(?array $expected, string $value): void
    {
        $this->assertSame($expected, StreetLexer::parseNumberSuffixRange($value));
    }
}
