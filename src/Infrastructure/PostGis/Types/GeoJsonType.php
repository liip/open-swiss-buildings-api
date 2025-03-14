<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Jsor\Doctrine\PostGIS\Types\GeometryType;

/**
 * This type has been taken from a PR on the doctrine-postgis repository
 * where the type has been introduced but on a wrong namespace together with
 * other changes.
 *
 * See: https://github.com/jsor/doctrine-postgis/pull/61
 */
final class GeoJsonType extends GeometryType
{
    public const string NAME = 'geojson';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        return \sprintf('ST_AsGeoJSON(%s)', $sqlExpr);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        return $value;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return \sprintf('ST_GeomFromGeoJSON(%s)::geometry', $sqlExpr);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return parent::convertToDatabaseValue(json_encode($value), $platform);
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        /** @var array{geometry_type?: string|null, srid?: int|string|null} $column */
        $options = $this->getNormalizedPostGISColumnOptions($column);

        return \sprintf(
            '%s(%s, %d)',
            parent::getName(),
            $options['geometry_type'],
            $options['srid'],
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
