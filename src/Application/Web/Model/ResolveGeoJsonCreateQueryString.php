<?php

declare(strict_types=1);

namespace App\Application\Web\Model;

use App\Infrastructure\PostGis\SRIDEnum;

final class ResolveGeoJsonCreateQueryString
{
    /**
     * The EPSG Spatial-Reference ID (see https://epsg.io/4326) override for the provided GeoJson.
     * This will override the (deprecated) Reference-Coordinate-System 'crs' GeoJson property, if defined.
     *
     * If none is defined, nor the override, nor the 'crs' in the GeoJson contents, then the contents will be
     * handled according to the RFC-7946 https://datatracker.ietf.org/doc/html/rfc7946 by using the WGS84 system.
     */
    public ?SRIDEnum $srid = null;
}
