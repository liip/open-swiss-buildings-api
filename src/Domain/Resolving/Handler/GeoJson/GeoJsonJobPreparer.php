<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\GeoJson;

use App\Domain\Resolving\Contract\GeoJsonCoordinatesParserInterface;
use App\Domain\Resolving\Contract\GeoJsonFeatureParserInterface;
use App\Domain\Resolving\Contract\Job\JobPreparerInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\WriteResolverTask;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\IO\GeoJSONWriter;

final readonly class GeoJsonJobPreparer implements JobPreparerInterface
{
    public function __construct(
        private ResolverTaskWriteRepositoryInterface $taskRepository,
        private ResolverMetadataWriteRepositoryInterface $metadataRepository,
        private GeoJsonCoordinatesParserInterface $geoJsonCoordinatesParser,
        private GeoJsonFeatureParserInterface $geoJsonFeatureParser,
    ) {}

    public function canPrepareJob(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::GEO_JSON === $type;
    }

    public function prepareJob(ResolverJobRawData $jobData): void
    {
        $tasks = $this->readTasks($jobData);
        try {
            $this->taskRepository->store($tasks);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'ST_Transform: Input geometry has unknown (0) SRID')) {
                throw new InvalidInputDataException('Error handling GeoJson coordinate system conversion: possible SRID mismatch', $e);
            }

            throw $e;
        }
    }

    /**
     * @return iterable<WriteResolverTask>
     *
     * @throws InvalidInputDataException
     */
    private function readTasks(ResolverJobRawData $jobData): iterable
    {
        $jobResource = $jobData->getResource();

        if (!\is_resource($jobResource) || false === $contents = stream_get_contents($jobResource)) {
            throw new InvalidInputDataException('Unable to load raw data from the Job resource');
        }

        $geoJsonSRID = $jobData->metadata->geoJsonSRID;

        // We validate if the provided SRID is aligned with the one guessed from the contents
        // If we would accept more SRIDs and any of them could have overlapping coordinates, we would
        // instead have to validate by looking if the first polygon fits into the area of our SRID.
        if (null !== $geoJsonSRID) {
            $guessSRID = $this->geoJsonCoordinatesParser->guessSRIDfromJsonContents($contents);
            if (null !== $guessSRID && $guessSRID->value !== $geoJsonSRID) {
                throw new InvalidInputDataException("Invalid GeoJson coordinate system: mismatch from provided SRID, got {$geoJsonSRID} but identified {$guessSRID->value}");
            }
        }

        try {
            // If no SRID is defined on the metadata, we extract it from the GeoJson
            $geoJsonSRID = $geoJsonSRID ?? $this->geoJsonCoordinatesParser->extractSRIDFromGeoJson($contents);

            $geoJson = (new GeoJSONReader())->read($contents);
            unset($contents);
        } catch (\JsonException $e) {
            throw new InvalidInputDataException('Invalid JSON contents', $e);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidInputDataException('Invalid GeoJson coordinate system', $e);
        } catch (GeometryException $e) {
            throw new InvalidInputDataException('Invalid GeoJson geometry data', $e);
        }

        if (!$geoJson instanceof FeatureCollection) {
            throw new InvalidInputDataException('Invalid data: expected a GeoJson FeatureCollection, found ' . get_debug_type($geoJson));
        }

        $metadata = $jobData->metadata->withGeoJsonSRID($geoJsonSRID);
        $propertiesName = $this->geoJsonFeatureParser->extractAggregatedPropertiesName($geoJson);
        if ([] !== $propertiesName) {
            $metadata = $metadata->withAdditionalColumns($propertiesName);
        }
        $this->metadataRepository->updateMetadata($jobData->id, $metadata);

        $writer = new GeoJSONWriter();
        try {
            foreach ($geoJson->getFeatures() as $feature) {
                $geometry = $feature->getGeometry();
                if (null === $geometry) {
                    continue;
                }

                // Force the geometry to be 2D to make the matching work
                $geoJsonMatch = $writer->writeRaw($geometry->toXY());
                // We add a (legacy) Coordinate-Reference-System (CRS) information to the GeoJson, so that the coordinates
                // are properly converted and handled by the PosGIS database
                $geoJsonMatch->crs = (object) $this->geoJsonCoordinatesParser->buildLegacyCoordinateReferenceSystem($geoJsonSRID);

                $additionalData = $this->geoJsonFeatureParser->extractPropertiesValues($feature, $propertiesName);

                yield WriteResolverTask::forGeoJsonJob(
                    jobId: $jobData->id,
                    confidence: 100,
                    matchingGeoJson: json_encode($geoJsonMatch, \JSON_THROW_ON_ERROR),
                    additionalData: AdditionalData::create($additionalData),
                );
            }
        } catch (\Exception $e) {
            throw InvalidInputDataException::wrap($e);
        }
    }
}
