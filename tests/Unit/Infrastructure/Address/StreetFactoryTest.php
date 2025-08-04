<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Address;

use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Address\Model\StreetNumberInterface;
use App\Infrastructure\Address\StreetFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class StreetFactoryTest extends TestCase
{
    public function testStreetWithoutNumberIsCreated(): void
    {
        $s = StreetFactory::createFromSeparateStrings('Hauptstrasse', null);

        $this->assertNotInstanceOf(StreetNumberInterface::class, $s->number);
    }

    /**
     * @param non-empty-string $number
     */
    #[DataProvider('provideHouseNumbers')]
    public function testNumberIsCreated(StreetNumber $expected, string $number): void
    {
        $n = StreetFactory::createNumberFromString($number);

        $this->assertInstanceOf(StreetNumber::class, $n);
        $this->assertTrue($n->equalsTo($expected), var_export($n, true));
    }

    /**
     * @param non-empty-string $number
     */
    #[DataProvider('provideHouseNumbers')]
    public function testStreetWithNumberIsCreated(StreetNumber $expected, string $number): void
    {
        $s = StreetFactory::createFromSeparateStrings('Hauptstrasse', $number);

        $this->assertInstanceOf(StreetNumberInterface::class, $s->number);
        $this->assertTrue($s->number->equalsTo($expected), var_export($s->number, true));
    }

    /**
     * @return iterable<array{StreetNumber, non-empty-string}>
     */
    public static function provideHouseNumbers(): iterable
    {
        yield [new StreetNumber(1), '1'];
        yield [new StreetNumber(1, 'b'), '1 b'];
        yield [new StreetNumber(1, 'b'), '1b'];
        yield [new StreetNumber(1, '.1'), '1.1'];
        yield [new StreetNumber(null, '.1'), '.1'];
        yield [new StreetNumber(null, 'b'), 'b'];
        yield [new StreetNumber(35, 'b.1'), '35b.1'];
        yield [new StreetNumber(15, '.e15'), '15.e15'];
    }
}
