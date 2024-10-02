<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\GeoJson;

use Brick\Geo\Point;

final class ProjectionSystemBoundaries
{
    private function __construct() {}

    public static function isPointInWGS84Boundaries(Point $point): bool
    {
        // See: https://epsg.io/4326 for boundaries
        return ($point->x() > -180.0 && $point->x() < 180.0)
            && ($point->y() > -90.0 && $point->y() < 90.0);
    }

    public static function isPointInLV95Boundaries(Point $point): bool
    {
        // See: https://epsg.io/2056 for boundaries
        return ($point->x() > 2485071.58 && $point->x() < 2837119.8)
            && ($point->y() > 1074261.72 && $point->y() < 1299941.79);
    }
}
