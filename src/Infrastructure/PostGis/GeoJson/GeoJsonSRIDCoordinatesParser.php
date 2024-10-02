<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\GeoJson;

use App\Domain\Resolving\Contract\GeoJsonCoordinatesParserInterface;
use App\Infrastructure\PostGis\SRIDEnum;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\MultiPolygon;
use Brick\Geo\Point;
use Brick\Geo\Polygon;

final class GeoJsonSRIDCoordinatesParser implements GeoJsonCoordinatesParserInterface
{
    public function extractSRIDFromGeoJson(string $contents): int
    {
        return $this->identifySRID($contents)->value;
    }

    public function buildLegacyCoordinateReferenceSystem(int $srid): ?array
    {
        if (null === $rsidEnum = SRIDEnum::tryFrom($srid)) {
            return null;
        }

        return [
            'type' => 'name',
            'properties' => [
                'name' => $rsidEnum->getUrn(),
            ],
        ];
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function identifySRID(string $contents): SRIDEnum
    {
        $json = json_decode($contents, false, 512, \JSON_THROW_ON_ERROR);
        if (!\is_object($json)) {
            throw new \JsonException('GeoJson expecting object, got: ' . get_debug_type($json));
        }

        /*
         * We check if the following property exists in the JSON object:
         * crs: {
         *   type: "name",
         *   properties: {
         *     name: "urn:ogc:def:crs:OGC::CRS84"
         *   }
         * }
         */

        // No CRS property is defined, let's return WGS84 according to the RFC
        if (!property_exists($json, 'crs')) {
            return $this->guessSRIDfromJsonContents($contents) ?: SRIDEnum::WGS84;
        }

        if (!\is_object($json->crs)
            || !property_exists($json->crs, 'type')
            || !\is_string($json->crs->type)
            || 'name' !== $json->crs->type
            || !property_exists($json->crs, 'properties')
            || !\is_object($json->crs->properties)
            || !property_exists($json->crs->properties, 'name')
            || !\is_string($json->crs->properties->name)
        ) {
            throw new \InvalidArgumentException('Unable to identify GeoJson coordinate system, invalid CRS property');
        }

        $value = $json->crs->properties->name;

        $srid = SRIDEnum::tryFromUrn($value);

        if (null === $srid) {
            throw new \InvalidArgumentException("Unable to identify the GeoJson coordinate system from '{$value}'");
        }

        return $srid;
    }

    public function guessSRIDfromJsonContents(string $contents): ?SRIDEnum
    {
        try {
            $geoJson = (new GeoJSONReader())->read($contents);
        } catch (\Throwable $e) {
            return null;
        }

        // We handle only FeatureCollection
        if (!$geoJson instanceof FeatureCollection) {
            return null;
        }

        if (false === $feature = current($geoJson->getFeatures())) {
            return null;
        }

        $geometry = $feature->getGeometry();
        if (null === $geometry || $geometry->isEmpty()) {
            return null;
        }

        $point = match ($geometry::class) {
            MultiPolygon::class => GeoPointExtractor::extractPointFromMultiPolygon($geometry),
            Polygon::class => GeoPointExtractor::extractPointFromPolygon($geometry),
            default => null,
        };

        if (!$point instanceof Point) {
            return null;
        }

        return match (true) {
            ProjectionSystemBoundaries::isPointInLV95Boundaries($point) => SRIDEnum::LV95,
            ProjectionSystemBoundaries::isPointInWGS84Boundaries($point) => SRIDEnum::WGS84,
            default => null,
        };
    }
}
