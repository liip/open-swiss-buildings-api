<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\PostGis\GeoJson;

use App\Infrastructure\PostGis\GeoJson\GeoJsonFeatureParser;
use Brick\Geo\Io\GeoJson\FeatureCollection;
use Brick\Geo\Io\GeoJsonReader;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class GeoJsonFeatureParserTest extends TestCase
{
    private static GeoJsonReader $geoJsonReader;

    private GeoJsonFeatureParser $extractor;

    protected function setUp(): void
    {
        $this->extractor = new GeoJsonFeatureParser();
    }

    public static function setUpBeforeClass(): void
    {
        self::$geoJsonReader = new GeoJsonReader();
    }

    public function testExtractPropertyValues(): void
    {
        $geoJson = $this->getGeoJson(__DIR__ . '/fixtures/lv95_03_with_properties.geojson');

        $features = $geoJson->getFeatures();
        $this->assertCount(2, $features);

        $values = $this->extractor->extractPropertiesValues($features[0], ['id', 'prop2', 'prop3', 'prop4']);
        $this->assertSame(['id' => '0', 'prop2' => '', 'prop3' => '', 'prop4' => ''], $values);

        $values = $this->extractor->extractPropertiesValues($features[1], ['id', 'prop2', 'prop3', 'prop4']);
        $this->assertSame(['id' => '1', 'prop2' => 'X2', 'prop3' => '', 'prop4' => 'Z4'], $values);
    }

    public function testExtractAggregatedPropertiesNames(): void
    {
        $geoJson = $this->getGeoJson(__DIR__ . '/fixtures/lv95_03_with_properties.geojson');

        $expected = $this->extractor->extractAggregatedPropertiesName($geoJson);
        $this->assertSame([
            'id',
            'prop2',
            'prop3',
            'prop4',
        ], $expected);
    }

    private function getGeoJson(string $filename): FeatureCollection
    {
        if (false === $contents = file_get_contents($filename)) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $geoJson = self::$geoJsonReader->read($contents);

        if (!$geoJson instanceof FeatureCollection) {
            throw new \RuntimeException('Invalid data: expected a GeoJson FeatureCollection, found ' . get_debug_type($geoJson));
        }

        return $geoJson;
    }
}
