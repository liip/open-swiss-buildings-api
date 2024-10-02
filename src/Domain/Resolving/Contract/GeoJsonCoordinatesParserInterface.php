<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use App\Infrastructure\PostGis\SRIDEnum;

/**
 * @phpstan-type CRSAsArray array{type: string, properties: array{name: string}}
 */
interface GeoJsonCoordinatesParserInterface
{
    /**
     * @return non-negative-int
     *
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function extractSRIDFromGeoJson(string $contents): int;

    /**
     * Returns the SRID of the given GeoJson by guessing the projection system.
     * If no projection system can be determined, null is then returned.
     */
    public function guessSRIDfromJsonContents(string $contents): ?SRIDEnum;

    /**
     * @return CRSAsArray|null
     */
    public function buildLegacyCoordinateReferenceSystem(int $srid): ?array;
}
