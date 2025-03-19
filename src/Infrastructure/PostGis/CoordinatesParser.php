<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis;

use Brick\Geo\CoordinateSystem;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Io\EwktReader;
use Brick\Geo\Io\EwktWriter;
use Brick\Geo\Point;

final class CoordinatesParser
{
    /**
     * @return array{
     *     coords: array{east: string, north: string}|null,
     *     geoCoords: string,
     * }
     */
    public static function extractCoordsFromLV95ByParts(string $east, string $north): array
    {
        static $ewktWriter, $emptyPoint;
        $ewktWriter = $ewktWriter ?? new EwktWriter();
        $emptyPoint = $emptyPoint ?? $ewktWriter->write(new Point(CoordinateSystem::xy(SRIDEnum::LV95->value)));

        if ('' === $east || '' === $north) {
            return ['coords' => null, 'geoCoords' => $emptyPoint];
        }

        $point = Point::xy((float) $east, (float) $north, SRIDEnum::LV95->value);

        return [
            'coords' => ['east' => (string) $point->x(), 'north' => (string) $point->y()],
            'geoCoords' => $ewktWriter->write($point),
        ];
    }

    public static function parseWGS84(?string $coordinates): ?Coordinates
    {
        if (null === $coordinates) {
            return null;
        }

        static $ewktReader;
        $ewktReader = $ewktReader ?? new EwktReader();

        try {
            $point = $ewktReader->read($coordinates);

            if (!$point instanceof Point || SRIDEnum::WGS84->value !== $point->SRID()) {
                // If no point is given, or the point is empty or still on the LV95 format we skip the parsing
                // as getting a point with LV95 SRID means PostgreSQL did not convert the value yet.
                return null;
            }

            $x = (string) $point->x();
            $y = (string) $point->y();

            if ('' === $x || '' === $y) {
                return null;
            }

            return new Coordinates(
                latitude: $y,
                longitude: $x,
            );
        } catch (GeometryException) {
            return null;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct() {}
}
