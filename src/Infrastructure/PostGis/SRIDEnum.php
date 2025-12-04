<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis;

enum SRIDEnum: int
{
    case WGS84 = 4326;

    case LV95 = 2056;

    /**
     * Returns the URN recognized by PostGIS for the given SRID.
     *
     * Warning: looks like PostGis v3.3.2 does not recognize the "long" version of URNs,
     * so we return here its short-representation.
     */
    public function getUrn(): string
    {
        return match ($this) {
            self::WGS84 => 'EPSG:4326',
            self::LV95 => 'EPSG:2056',
        };
    }

    public static function tryFromUrn(string $urn): ?self
    {
        return match ($urn) {
            'EPSG:4326' => self::WGS84,
            'urn:ogc:def:crs:OGC:1.3:CRS84' => self::WGS84,
            'urn:ogc:def:crs:OGC::CRS84' => self::WGS84,
            'EPSG:2056' => self::LV95,
            'urn:ogc:def:crs:EPSG::2056' => self::LV95,
            default => null,
        };
    }
}
