<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\PostGis;

use App\Infrastructure\PostGis\Coordinates;
use App\Infrastructure\PostGis\CoordinatesParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(CoordinatesParser::class)]
final class CoordinatesParserTest extends TestCase
{
    /**
     * @param array{east: string, north: string}|null $expectedCoords
     */
    #[DataProvider('provideExtractCoordsFromLV95ByPartsCases')]
    public function testExtractCoordsFromLV95ByParts(?array $expectedCoords, string $expectedGeoCoords, string $east, string $north): void
    {
        $result = CoordinatesParser::extractCoordsFromLV95ByParts($east, $north);

        $this->assertSame($expectedCoords, $result['coords']);
        $this->assertSame($expectedGeoCoords, $result['geoCoords']);
    }

    /**
     * @return iterable<array{array{east: string, north: string}|null,string, string,string}>
     */
    public static function provideExtractCoordsFromLV95ByPartsCases(): iterable
    {
        yield [null, 'SRID=2056; POINT EMPTY', '', ''];
        yield [null, 'SRID=2056; POINT EMPTY', '2688354.766', ''];
        yield [null, 'SRID=2056; POINT EMPTY', '', '1255747.784'];
        yield [
            ['east' => '2688354.766', 'north' => '1255747.784'],
            'SRID=2056; POINT (2688354.766 1255747.784)',
            '2688354.766',
            '1255747.784',
        ];
    }

    #[DataProvider('provideParseWGS84ReturnsNullCases')]
    public function testParseWGS84ReturnsNull(?string $coordinates): void
    {
        $this->assertNotInstanceOf(Coordinates::class, CoordinatesParser::parseWGS84($coordinates));
    }

    /**
     * @return iterable<array{string|null}>
     */
    public static function provideParseWGS84ReturnsNullCases(): iterable
    {
        yield [null];
        yield [''];
        yield ['SRID=2056; POINT EMPTY'];
        yield ['SRID=4326; POINT EMPTY'];
        yield ['SRID=2056; POINT (2682348.561 1248943.136)'];
    }

    #[DataProvider('provideParseWGS84Cases')]
    public function testParseWGS84(Coordinates $expectedCoordinates, string $coordinates): void
    {
        $coords = CoordinatesParser::parseWGS84($coordinates);
        $this->assertInstanceOf(Coordinates::class, $coords);

        $this->assertSame($expectedCoordinates->latitude, $coords->latitude);
        $this->assertSame($expectedCoordinates->longitude, $coords->longitude);
    }

    /**
     * @return iterable<array{Coordinates, string}>
     */
    public static function provideParseWGS84Cases(): iterable
    {
        $coord = new Coordinates(latitude: '8.52929', longitude: '47.38623');
        yield [$coord, 'SRID=4326; POINT (47.38623 8.52929)'];
    }
}
