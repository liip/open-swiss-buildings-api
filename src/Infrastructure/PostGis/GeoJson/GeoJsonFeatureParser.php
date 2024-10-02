<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis\GeoJson;

use App\Domain\Resolving\Contract\GeoJsonFeatureParserInterface;
use Brick\Geo\IO\GeoJSON\Feature;
use Brick\Geo\IO\GeoJSON\FeatureCollection;

final class GeoJsonFeatureParser implements GeoJsonFeatureParserInterface
{
    /**
     * Given the FeatureCollection, extracts the aggregated list of property names.
     *
     * Note: the names are sorted
     *
     * @return list<string>
     */
    public function extractAggregatedPropertiesName(FeatureCollection $featureCollection): array
    {
        $names = [];
        foreach ($featureCollection->getFeatures() as $feature) {
            if (null === $properties = $feature->getProperties()) {
                continue;
            }

            $names = [...$names, ...array_keys((array) $properties)];
        }

        $names = array_unique($names);
        sort($names, \SORT_NATURAL);

        return $names;
    }

    /**
     * Gets the list of properties and their values from the given feature.
     * Only the provided property-names will be returned.
     *
     * If a property does not exist in the given feature, it will be returned with a default empty value.
     *
     * @param list<string> $propertyNames
     *
     * @return array<string, string>
     */
    public function extractPropertiesValues(Feature $feature, array $propertyNames): array
    {
        $map = [];
        foreach ($propertyNames as $propertyName) {
            $map[$propertyName] = (string) $feature->getProperty($propertyName, '');
        }

        return $map;
    }
}
