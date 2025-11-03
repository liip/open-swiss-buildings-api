<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Address\Parser;

use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Address\Model\StreetNumberRange;
use App\Infrastructure\Address\Model\StreetNumberSuffixRange;
use App\Infrastructure\Address\Parser\StreetParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class StreetParserTest extends TestCase
{
    /**
     * @param non-empty-string $street
     */
    #[DataProvider('provideStreetIsCreatedWithRangeCases')]
    public function testStreetIsCreatedWithRange(Street $expected, string $street): void
    {
        $street = StreetParser::createStreetFromString($street);
        $this->assertTrue($expected->equalsTo($street), (string) $street);
    }

    /**
     * @return iterable<array{Street, string}>
     */
    public static function provideStreetIsCreatedWithRangeCases(): iterable
    {
        $street = new Street('Chèvrerie des 4 Vents');
        yield [$street, 'Chèvrerie des 4 Vents'];

        $street = $street->withHouseNumber(new StreetNumber(null, '.2'));
        yield [$street, 'Chèvrerie des 4 Vents .2'];

        $street = $street->withHouseNumber(new StreetNumber(12, 'a'));
        // yield [$street, 'Chèvrerie des 4 Vents 12a'];
        yield [$street, 'Chèvrerie des 4 Vents 12 a'];

        $street = $street->withHouseNumber(new StreetNumber(12, 'A'));
        yield [$street, 'Chèvrerie des 4 Vents 12A'];
        yield [$street, 'Chèvrerie des 4 Vents 12 A'];

        $street = $street->withHouseNumber(new StreetNumber(12, '.2'));
        yield [$street, 'Chèvrerie des 4 Vents 12.2'];
        yield [$street, 'Chèvrerie des 4 Vents 12 .2'];

        $street = $street->withHouseNumber(new StreetNumber(12, '.a15'));
        yield [$street, 'Chèvrerie des 4 Vents 12.a15'];

        $street = $street->withHouseNumber(new StreetNumber(12, 'a.2'));
        yield [$street, 'Chèvrerie des 4 Vents 12a.2'];

        $street = $street->withHouseNumber(new StreetNumberRange(12, 14));
        yield [$street, 'Chèvrerie des 4 Vents 12-14'];
        yield [$street, 'Chèvrerie des 4 Vents 12 -14'];
        yield [$street, 'Chèvrerie des 4 Vents 12- 14'];
        yield [$street, 'Chèvrerie des 4 Vents 12 - 14'];

        $street = $street->withHouseNumber(new StreetNumberSuffixRange(12, 'a', 'b'));
        yield [$street, 'Chèvrerie des 4 Vents 12a-b'];
        yield [$street, 'Chèvrerie des 4 Vents 12a -b'];
        yield [$street, 'Chèvrerie des 4 Vents 12a- b'];
        yield [$street, 'Chèvrerie des 4 Vents 12a - b'];
        yield [$street, 'Chèvrerie des 4 Vents 12 a-b'];
        yield [$street, 'Chèvrerie des 4 Vents 12 a -b'];
        yield [$street, 'Chèvrerie des 4 Vents 12 a- b'];
        yield [$street, 'Chèvrerie des 4 Vents 12 a - b'];

        $street = new Street('Viale 1814', new StreetNumber(2));
        yield [$street, 'Viale 1814 2'];

        $street = new Street("Grand'Rue", new StreetNumber(19, 'A'));
        yield [$street, "Grand'Rue 19A"];

        $street = new Street(null, new StreetNumber(9, 'a'));
        yield [$street, '9a'];
        yield [$street, '9 a'];
        yield [$street, ' 9a'];
        yield [$street, ' 9 a'];

        $street = new Street('Chèvrerie');
        yield [$street, 'Chèvrerie 0'];
    }
}
