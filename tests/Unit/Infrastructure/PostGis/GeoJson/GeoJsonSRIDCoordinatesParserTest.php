<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\PostGis\GeoJson;

use App\Domain\Resolving\Contract\GeoJsonCoordinatesParserInterface;
use App\Infrastructure\PostGis\GeoJson\GeoJsonSRIDCoordinatesParser;
use App\Infrastructure\PostGis\SRIDEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type CRSAsArray from GeoJsonCoordinatesParserInterface
 */
#[Small]
#[CoversClass(GeoJsonSRIDCoordinatesParser::class)]
#[CoversClass(SRIDEnum::class)]
final class GeoJsonSRIDCoordinatesParserTest extends TestCase
{
    private const int WGS84 = 4326;
    public const int LV95 = 2056;

    /**
     * @return iterable<array{CRSAsArray|null, int}>
     */
    public static function createBuildLegacyCRSProvider(): iterable
    {
        yield [null, 0000];

        $expected = ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']];
        yield [$expected, self::WGS84];

        $expected = ['type' => 'name', 'properties' => ['name' => 'EPSG:2056']];
        yield [$expected, self::LV95];
    }

    /**
     * @param CRSAsArray|null $expected
     */
    #[DataProvider('createBuildLegacyCRSProvider')]
    public function testBuildLegacyCRS(?array $expected, int $srid): void
    {
        $parser = new GeoJsonSRIDCoordinatesParser();
        $struct = $parser->buildLegacyCoordinateReferenceSystem($srid);
        $this->assertSame($expected, $struct);
    }

    /**
     * @return iterable<array{?int, string}>
     */
    public static function guessSRIDfromContentsProvider(): iterable
    {
        yield 'lv95_01' => [self::LV95, __DIR__ . '/fixtures/lv95_01.geojson'];
        yield 'lv95_02' => [self::LV95, __DIR__ . '/fixtures/lv95_02.geojson'];
        yield 'wgs84_01' => [self::WGS84, __DIR__ . '/fixtures/wgs84_01.geojson'];
        yield 'wgs84_02' => [self::WGS84, __DIR__ . '/fixtures/wgs84_02.geojson'];

        // The following fixtures are not supported, thus expect to return NULL as "guess"
        yield 'wgs84_polygon' => [null, __DIR__ . '/fixtures/wgs84_03_polygon.geojson'];
        yield 'empty_obj' => [null, __DIR__ . '/fixtures/empty_object.geojson'];
        yield 'empty_collection' => [null, __DIR__ . '/fixtures/empty_polygon.geojson'];
        yield 'empty_features' => [null, __DIR__ . '/fixtures/empty_features.geojson'];
    }

    #[DataProvider('guessSRIDfromContentsProvider')]
    public function testGuessSRIDfromContents(?int $expectedRsid, string $file): void
    {
        if (false === $contents = file_get_contents($file)) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $parser = new GeoJsonSRIDCoordinatesParser();
        $rsid = $parser->guessSRIDfromJsonContents($contents);

        $this->assertSame($expectedRsid, null !== $rsid ? $rsid->value : null);
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function createIdentifySRIDProvider(): iterable
    {
        // Files with no CRS, testing "guessing" feature
        yield 'lv95_01' => [self::LV95, __DIR__ . '/fixtures/lv95_01.geojson'];
        yield 'lv95_02' => [self::LV95, __DIR__ . '/fixtures/lv95_02.geojson'];
        yield 'wgs84_01' => [self::WGS84, __DIR__ . '/fixtures/wgs84_01.geojson'];
        yield 'wgs84_02' => [self::WGS84, __DIR__ . '/fixtures/wgs84_02.geojson'];
        yield 'wgs84_polygon' => [self::WGS84, __DIR__ . '/fixtures/wgs84_03_polygon.geojson'];
        yield 'empty_obj' => [self::WGS84, __DIR__ . '/fixtures/empty_object.geojson'];
        yield 'empty_collection' => [self::WGS84, __DIR__ . '/fixtures/empty_polygon.geojson'];
        yield 'empty_features' => [self::WGS84, __DIR__ . '/fixtures/empty_features.geojson'];

        // Files with CRS defined
        yield 'crs_lv95' => [self::LV95, __DIR__ . '/fixtures/crs_epsg2056.geojson'];
        yield 'crs_lv95_full' => [self::LV95, __DIR__ . '/fixtures/crs_epsg2056_full_urn.geojson'];
        yield 'crs_wgs84' => [self::WGS84, __DIR__ . '/fixtures/crs_epsg4326.geojson'];
        yield 'crs_wgs84_ogc' => [self::WGS84, __DIR__ . '/fixtures/crs_ogc_crs84.geojson'];
        yield 'crs_wgs84_ogc13' => [self::WGS84, __DIR__ . '/fixtures/crs_ogc_13_crs84.geojson'];
    }

    #[DataProvider('createIdentifySRIDProvider')]
    public function testIdentifySRID(int $expectedRsid, string $file): void
    {
        if (false === $contents = file_get_contents($file)) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $parser = new GeoJsonSRIDCoordinatesParser();
        $rsid = $parser->identifySRID($contents);

        $this->assertSame($expectedRsid, $rsid->value);
    }

    #[DataProvider('createIdentifySRIDProvider')]
    public function testExtractSRIDFromGeoJson(int $expectedSRID, string $file): void
    {
        if (false === $contents = file_get_contents($file)) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $parser = new GeoJsonSRIDCoordinatesParser();
        $rsid = $parser->extractSRIDFromGeoJson($contents);

        $this->assertSame($expectedSRID, $rsid);
    }

    /**
     * @return iterable<array{string, class-string<\Throwable>, string}>
     */
    public static function createIdentifyRSIDThrowsProvider(): iterable
    {
        yield ['Syntax error', \JsonException::class, ''];
        yield ['Syntax error', \JsonException::class, '{'];
        yield ['Syntax error', \JsonException::class, '['];
        yield ['GeoJson expecting object, got: null', \JsonException::class, 'null'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":null }'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":123 }'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":[] }'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":null}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":123}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":[]}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":{}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name"}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"properties":null}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"properties":123}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"properties":[]}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"properties":{}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":null}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":123}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":[]}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":{}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":{"name":null}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":{"name":123}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":{"name":[]}}}'];
        yield ['invalid CRS property', \InvalidArgumentException::class, '{"crs":{"type":"name","properties":{"name":{}}}}'];
        yield ['Unable to identify the GeoJson coordinate system', \InvalidArgumentException::class, '{"crs": { "type": "name", "properties": { "name": "urn:xxx" } }}'];
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('createIdentifyRSIDThrowsProvider')]
    public function testIdentifySRIDThrows(string $expectedMessage, string $exceptionClass, string $contents): void
    {
        $parser = new GeoJsonSRIDCoordinatesParser();
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($expectedMessage);

        $parser->identifySRID($contents);
    }
}
