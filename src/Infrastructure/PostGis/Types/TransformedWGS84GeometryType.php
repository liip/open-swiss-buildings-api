<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\Types;

use App\Infrastructure\PostGis\SRIDEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Jsor\Doctrine\PostGIS\Types\PostGISType;

/**
 * Stores the geometry data by transforming the original data into the WGS84 geometry.
 *
 * @See https://epsg.io/4326 for the geometry reference.
 */
final class TransformedWGS84GeometryType extends PostGISType
{
    public const string NAME = 'transformed_wgs84_geometry';

    /**
     * @param array{geometry_type?: string|null, srid?: int|string|null} $options
     *
     * @return array{geometry_type: string, srid: int} $column
     */
    public function getNormalizedPostGISColumnOptions(array $options = []): array
    {
        return [
            'geometry_type' => strtoupper($options['geometry_type'] ?? 'GEOMETRY'),
            'srid' => SRIDEnum::WGS84->value,
        ];
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return \sprintf('ST_Transform(ST_GeomFromEWKT(%s), %d)', $sqlExpr, SRIDEnum::WGS84->value);
    }

    /** @param array{geometry_type?: string|null, srid?: int|string|null} $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $options = $this->getNormalizedPostGISColumnOptions($column);

        return \sprintf(
            '%s(%s, %d)',
            PostGISType::GEOMETRY,
            $options['geometry_type'],
            $options['srid'],
        );
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * This requires the SQL comment hint, as we override the "geometry" type.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
