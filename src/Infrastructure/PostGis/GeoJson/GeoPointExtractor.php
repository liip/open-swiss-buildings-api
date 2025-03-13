<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\GeoJson;

use Brick\Geo\Geometry;
use Brick\Geo\LineString;
use Brick\Geo\MultiPolygon;
use Brick\Geo\Point;
use Brick\Geo\Polygon;

final class GeoPointExtractor
{
    private function __construct() {}

    public static function extractPointFromMultiPolygon(MultiPolygon $multiPolygon): ?Point
    {
        foreach ($multiPolygon->geometries() as $geometryItem) {
            foreach ($geometryItem->getIterator() as $item) {
                /** @phpstan-var LineString|Geometry $item */
                if (!$item instanceof LineString) {
                    continue;
                }

                return $item->startPoint();
            }
        }

        return null;
    }

    public static function extractPointFromPolygon(Polygon $multiPolygon): ?Point
    {
        foreach ($multiPolygon->getIterator() as $item) {
            /** @phpstan-var LineString|Geometry $item */
            if (!$item instanceof LineString) {
                continue;
            }

            return $item->startPoint();
        }

        return null;
    }
}
