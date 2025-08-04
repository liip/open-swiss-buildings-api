<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving\Handler\GeoJson;

use App\Domain\Resolving\Contract\GeoJsonCoordinatesParserInterface;
use App\Domain\Resolving\Contract\GeoJsonFeatureParserInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Exception\ResolverJobFailedException;
use App\Domain\Resolving\Handler\GeoJson\GeoJsonJobPreparer;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\Job\WriteResolverTask;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\PostGis\SRIDEnum;
use Brick\Geo\Io\GeoJson\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[Small]
final class GeoJsonJobPrepareTest extends TestCase
{
    private ResolverTaskWriteRepositoryInterface&MockObject $taskRepository;
    private ResolverMetadataWriteRepositoryInterface&MockObject $metadataRepository;
    private GeoJsonCoordinatesParserInterface&MockObject $geoJsonCoordinatesParser;
    private GeoJsonFeatureParserInterface&MockObject $geoJsonFeatureParser;

    private GeoJsonJobPreparer $jobPreparer;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(ResolverTaskWriteRepositoryInterface::class);
        $this->metadataRepository = $this->createMock(ResolverMetadataWriteRepositoryInterface::class);
        $this->geoJsonCoordinatesParser = $this->createMock(GeoJsonCoordinatesParserInterface::class);
        $this->geoJsonFeatureParser = $this->createMock(GeoJsonFeatureParserInterface::class);

        $this->jobPreparer = new GeoJsonJobPreparer(
            $this->taskRepository,
            $this->metadataRepository,
            $this->geoJsonCoordinatesParser,
            $this->geoJsonFeatureParser,
        );
    }

    #[DataProvider('provideCanPrepareJobCases')]
    public function testCanPrepareJob(bool $expected, ResolverTypeEnum $type): void
    {
        $this->assertSame($expected, $this->jobPreparer->canPrepareJob($type));
    }

    /**
     * @return iterable<array{bool, ResolverTypeEnum}>
     */
    public static function provideCanPrepareJobCases(): iterable
    {
        foreach (ResolverTypeEnum::cases() as $type) {
            yield [ResolverTypeEnum::GEO_JSON === $type, $type];
        }
    }

    public function testPrepareJobThrowsOnClosedResource(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);
        fclose($resource);
        $resolverData = new ResolverJobRawData(Uuid::v7(), ResolverTypeEnum::GEO_JSON, $resource, new ResolverMetadata());
        $this->metadataRepository->expects($this->never())->method('updateMetadata');
        $this->geoJsonCoordinatesParser->expects($this->never())->method('extractSRIDFromGeoJson');
        $this->setupRepositoryForNoTasks();

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Unable to load raw data from the Job resource');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnSRIDMismatchWithLV95Exception(): void
    {
        $metadata = (new ResolverMetadata())->withGeoJsonSRID(SRIDEnum::LV95->value);
        $resolverData = $this->createData('', $metadata);

        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('guessSRIDfromJsonContents')
            ->willReturn(SRIDEnum::WGS84)
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Invalid GeoJson coordinate system: mismatch from provided SRID, got 2056 but identified 4326');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnSRIDMismatchWithWGS84Exception(): void
    {
        $metadata = (new ResolverMetadata())->withGeoJsonSRID(SRIDEnum::WGS84->value);
        $resolverData = $this->createData('', $metadata);

        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('guessSRIDfromJsonContents')
            ->willReturn(SRIDEnum::LV95)
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Invalid GeoJson coordinate system: mismatch from provided SRID, got 4326 but identified 2056');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnJsonException(): void
    {
        $resolverData = $this->createData('');
        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('extractSRIDFromGeoJson')
            ->willThrowException(new \JsonException('Invalid input'))
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Invalid JSON contents');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnInvalidArgumentException(): void
    {
        $resolverData = $this->createData('');
        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('extractSRIDFromGeoJson')
            ->willThrowException(new \InvalidArgumentException('Invalid argument'))
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Invalid GeoJson coordinate system');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnInvalidGeometryException(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/empty-object.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $resolverData = $this->createData($contents);
        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('extractSRIDFromGeoJson')
            ->willReturn(SRIDEnum::LV95->value)
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data provided for the job: Invalid GeoJson geometry data');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnNorFeatureGeoJsonFile(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/point.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $resolverData = $this->createData($contents);
        $this->setupRepositoryForNoTasks();
        $this->metadataRepository->expects($this->never())->method('updateMetadata');

        $this->geoJsonCoordinatesParser->expects($this->once())
            ->method('extractSRIDFromGeoJson')
            ->willReturn(SRIDEnum::LV95->value)
        ;

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data: expected a GeoJson FeatureCollection, found');
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobWithLV95GeoJsonFile(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/multi-polygon-lv95.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->willReturnCallback(function (Uuid $id, ResolverMetadata $metadata): void {
                $this->assertNotNull($metadata->geoJsonSRID);
                $this->assertSame(SRIDEnum::LV95->value, $metadata->geoJsonSRID);
                $this->assertSame(['id', 'prop2', 'prop3', 'prop4'], $metadata->additionalColumns);
            })
        ;

        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willReturnCallback(
                /** @param iterable<WriteResolverTask> $tasks */
                function (iterable $tasks): void {
                    /** @var list<WriteResolverTask> $data */
                    $data = iterator_to_array($tasks);
                    $this->assertCount(2, $data);

                    $this->assertSameWriteResolverTask($data[0], ['id' => '0', 'prop2' => '', 'prop3' => '', 'prop4' => '']);
                    $this->assertSameWriteResolverTask($data[1], ['id' => '1', 'prop2' => 'X2', 'prop3' => 'Y2', 'prop4' => 'four']);
                },
            )
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractAggregatedPropertiesName')
            ->willReturn(['id', 'prop2', 'prop3', 'prop4'])
        ;

        $spy = $this->exactly(2);
        $this->geoJsonFeatureParser->expects($spy)
            ->method('extractPropertiesValues')
            ->willReturnCallback(function (Feature $feature, array $names) use ($spy): array {
                $this->assertSame(['id', 'prop2', 'prop3', 'prop4'], $names);

                return match ($spy->numberOfInvocations()) {
                    1 => ['id' => '0', 'prop2' => '',   'prop3' => '', 'prop4' => ''],
                    2 => ['id' => '1', 'prop2' => 'X2', 'prop3' => 'Y2', 'prop4' => 'four'],
                    default => throw new \RuntimeException('Method was not expected to be called more than 2 times'),
                };
            })
        ;

        $this->setupCoordinateParser(SRIDEnum::LV95, true, 2);

        $resolverData = $this->createData($contents);
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobWithLV95GeoJsonFileWitEmptyFeatureProperties(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/multi-polygon-lv95-empty-feature-properties.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->willReturnCallback(function (Uuid $id, ResolverMetadata $metadata): void {
                $this->assertNotNull($metadata->geoJsonSRID);
                $this->assertSame(SRIDEnum::LV95->value, $metadata->geoJsonSRID);
                $this->assertNull($metadata->additionalColumns);
            })
        ;

        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willReturnCallback(
                /** @param iterable<WriteResolverTask> $tasks */
                function (iterable $tasks): void {
                    /** @var list<WriteResolverTask> $data */
                    $data = iterator_to_array($tasks);
                    $this->assertCount(2, $data);

                    foreach ($data as $task) {
                        $this->assertSameWriteResolverTask($task, []);
                    }
                },
            )
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractAggregatedPropertiesName')
            ->willReturn([])
        ;

        $this->geoJsonFeatureParser->expects($this->exactly(2))
            ->method('extractPropertiesValues')
            ->with($this->anything(), [])
            ->willReturn([])
        ;

        $this->setupCoordinateParser(SRIDEnum::LV95, true, 2);

        $resolverData = $this->createData($contents);
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobWithWGS84GeoJsonFile(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/multi-polygon-wgs84.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->willReturnCallback(function (Uuid $id, ResolverMetadata $metadata): void {
                $this->assertNotNull($metadata->geoJsonSRID);
                $this->assertSame(SRIDEnum::WGS84->value, $metadata->geoJsonSRID);
                $this->assertSame(['prop1'], $metadata->additionalColumns);
            })
        ;

        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willReturnCallback(
                /** @param iterable<WriteResolverTask> $tasks */
                function (iterable $tasks): void {
                    /** @var list<WriteResolverTask> $data */
                    $data = iterator_to_array($tasks);
                    $this->assertCount(1, $data);

                    $this->assertSameWriteResolverTask($data[0]);
                },
            )
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractAggregatedPropertiesName')
            ->willReturn(['prop1'])
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractPropertiesValues')
            ->with($this->anything(), ['prop1'])
            ->willReturn(['prop1' => '111'])
        ;

        $this->setupCoordinateParser(SRIDEnum::WGS84, true, 1);

        $resolverData = $this->createData($contents);
        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobWithNoCRSGeoJsonFileAndOverride(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/multi-polygon-lv95-no-crs.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->willReturnCallback(function (Uuid $id, ResolverMetadata $metadata): void {
                $this->assertNotNull($metadata->geoJsonSRID);
                $this->assertSame(SRIDEnum::LV95->value, $metadata->geoJsonSRID);
                $this->assertSame(['prop1'], $metadata->additionalColumns);
            })
        ;

        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willReturnCallback(
                /** @param iterable<WriteResolverTask> $tasks */
                function (iterable $tasks): void {
                    /** @var list<WriteResolverTask> $data */
                    $data = iterator_to_array($tasks);
                    $this->assertCount(1, $data);

                    $this->assertSameWriteResolverTask($data[0]);
                },
            )
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractAggregatedPropertiesName')
            ->willReturn(['prop1'])
        ;

        $this->geoJsonFeatureParser->expects($this->once())
            ->method('extractPropertiesValues')
            ->with($this->anything(), ['prop1'])
            ->willReturn(['prop1' => '111'])
        ;

        $this->setupCoordinateParser(SRIDEnum::LV95, false, 1);

        $metadata = (new ResolverMetadata())->withGeoJsonSRID(SRIDEnum::LV95->value);
        $resolverData = $this->createData($contents, $metadata);

        $this->jobPreparer->prepareJob($resolverData);
    }

    public function testPrepareJobThrowsOnSRIDMismatch(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/multi-polygon-wgs84.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }

        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willThrowException(
                ResolverJobFailedException::wrap(new \Exception('An exception occurred while executing a query: SQLSTATE[XX000]: Internal error: 7 ERROR:  ST_Transform: Input geometry has unknown (0) SRID')),
            )
        ;

        $metadata = (new ResolverMetadata())->withGeoJsonSRID(SRIDEnum::LV95->value);
        $resolverData = $this->createData($contents, $metadata);

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Error handling GeoJson coordinate system conversion: possible SRID mismatch');
        $this->jobPreparer->prepareJob($resolverData);
    }

    private function setupRepositoryForNoTasks(): void
    {
        $this->taskRepository->expects($this->once())
            ->method('store')
            ->willReturnCallback(
                /** @param iterable<WriteResolverTask> $tasks */
                static function (iterable $tasks): void {
                    iterator_to_array($tasks);
                },
            )
        ;
    }

    private function createData(string $contents, ?ResolverMetadata $metadata = null): ResolverJobRawData
    {
        $rawData = fopen('php://memory', 'r+');
        $this->assertIsResource($rawData);

        fwrite($rawData, $contents);
        rewind($rawData);

        return new ResolverJobRawData(Uuid::v7(), ResolverTypeEnum::GEO_JSON, $rawData, $metadata ?? new ResolverMetadata());
    }

    /**
     * @param array<string, string> $additionalData
     */
    private function assertSameWriteResolverTask(
        WriteResolverTask $task,
        array $additionalData = ['prop1' => '111'],
        string $matchingGeoJson = '"crs":{"dummy":"for-matching"}',
    ): void {
        $this->assertSame(ResolverTypeEnum::GEO_JSON, $task->jobType);
        $this->assertNull($task->matchingBuildingId);
        $this->assertSame([] === $additionalData, $task->additionalData->isEmpty());
        $this->assertSame($additionalData, $task->additionalData->jsonSerialize());
        $this->assertNotNull($task->matchingGeoJson);
        $this->assertStringContainsString($matchingGeoJson, (string) $task->matchingGeoJson);
    }

    private function setupCoordinateParser(SRIDEnum $srid, bool $extract, int $buildInvokations): void
    {
        $this->geoJsonCoordinatesParser->expects($this->exactly($extract ? 1 : 0))
            ->method('extractSRIDFromGeoJson')
            ->willReturn($srid->value)
        ;

        $this->geoJsonCoordinatesParser->expects($this->exactly($buildInvokations))
            ->method('buildLegacyCoordinateReferenceSystem')
            ->with($srid->value)
            ->willReturn(['dummy' => 'for-matching'])
        ;
    }
}
