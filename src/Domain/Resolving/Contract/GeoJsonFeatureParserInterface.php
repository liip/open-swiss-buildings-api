<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use Brick\Geo\Io\GeoJson\Feature;
use Brick\Geo\Io\GeoJson\FeatureCollection;

interface GeoJsonFeatureParserInterface
{
    /**
     * Given the FeatureCollection, extracts the aggregated list of property names.
     *
     * Note: the names are prefixed and sorted
     *
     * @return list<non-empty-string>
     */
    public function extractAggregatedPropertiesName(FeatureCollection $featureCollection): array;

    /**
     * Gets the list of properties with their values, and combine them with the base properties for the given feature.
     *
     * @param list<non-empty-string> $propertyNames
     *
     * @return array<string, string>
     */
    public function extractPropertiesValues(Feature $feature, array $propertyNames): array;
}
