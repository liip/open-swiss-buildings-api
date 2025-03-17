<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Tests\Util\ResolvingApi;
use PHPUnit\Framework\Attributes\Large;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Large]
final class ResolverJobGeoJsonTest extends WebTestCase
{
    private ResolvingApi $api;
    private const int DEFAULT_COLUMNS_COUNT = 13;

    protected function setUp(): void
    {
        $this->api = new ResolvingApi(
            self::createClient(),
            self::getContainer(),
        );
    }

    protected function tearDown(): void
    {
        $this->api->tearDown();
        parent::tearDown();
    }

    public function testResolverJobIsCreated(): void
    {
        if (false === $contents = file_get_contents(__DIR__ . '/fixtures/resolving_polygon_1_LV95.geojson')) {
            throw new \RuntimeException('Unable to read fixtures file for testing');
        }
        $job = $this->api->createJob(ResolverTypeEnum::GEO_JSON, $contents);
        $this->assertSame(ResolverTypeEnum::GEO_JSON, $job->type);

        $jobInfo = $this->api->getJobInfo($job->id);
        $this->assertSame($job->id, $jobInfo->id);
        $this->assertSame(ResolverTypeEnum::GEO_JSON, $jobInfo->type);
    }

    public function testResolverJobResultIsComputedAndFetchedAsJsonWithLV95NoUserdata(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_nouserdata_1_LV95.geojson');
        $data = $this->api->getJobResultsAsJson($entity->id);

        $this->assertArrayHasKey('results', $data);
        $this->assertIsArray($data['results']);
        $this->assertCount(1, $data['results']);
        $this->assertSameArrayRecursive($this->getExpectedVseResultAsJson(), $data['results'][0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsJsonWithWGS84NoUserdata(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_nouserdata_1_WGS84.geojson');
        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);

        $columnNames = array_keys($rows[0]->data);
        $this->assertCount(self::DEFAULT_COLUMNS_COUNT, $columnNames);

        $this->api->assertCsvRow($this->getExpectedVseResultFromCSV(), $rows[0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsJsonWithLV95NoCrs(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_nocrs_1_LV95.geojson');
        $data = $this->api->getJobResultsAsJson($entity->id);

        $this->assertArrayHasKey('results', $data);
        $this->assertIsArray($data['results']);
        $this->assertCount(1, $data['results']);
        $this->assertSameArrayRecursive($this->getExpectedVseResultAsJson([
            'prop1' => 'one',
            'prop2' => '2',
        ]), $data['results'][0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsJsonWithLV95(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_1_LV95.geojson');
        $data = $this->api->getJobResultsAsJson($entity->id);

        $this->assertArrayHasKey('results', $data);
        $this->assertIsArray($data['results']);
        $this->assertCount(1, $data['results']);
        $this->assertSameArrayRecursive($this->getExpectedVseResultAsJson([
            'prop1' => 'one',
            'prop2' => '2',
        ]), $data['results'][0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsCsvWithLV95(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_1_LV95.geojson');
        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);

        // Test that the additional columns are added in the right order
        $columnNames = array_keys($rows[0]->data);
        $this->assertCount(self::DEFAULT_COLUMNS_COUNT + 2, $columnNames);
        $this->assertSame('userdata.prop1', $columnNames[self::DEFAULT_COLUMNS_COUNT]);
        $this->assertSame('userdata.prop2', $columnNames[self::DEFAULT_COLUMNS_COUNT + 1]);

        $this->api->assertCsvRow($this->getExpectedVseResultFromCSV([
            'userdata.prop1' => 'one',
            'userdata.prop2' => '2',
        ]), $rows[0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsCsvWithWGS84(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_1_WGS84.geojson');
        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);

        // Test that the additional columns are added in the right order
        $columnNames = array_keys($rows[0]->data);
        $this->assertCount(self::DEFAULT_COLUMNS_COUNT + 2, $columnNames);
        $this->assertSame('userdata.prop1', $columnNames[self::DEFAULT_COLUMNS_COUNT]);
        $this->assertSame('userdata.prop2', $columnNames[self::DEFAULT_COLUMNS_COUNT + 1]);

        $this->api->assertCsvRow($this->getExpectedVseResultFromCSV([
            'userdata.prop1' => 'one',
            'userdata.prop2' => '2',
        ]), $rows[0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsCsvWithWGS84OverlappingPolygons(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_overlapping_1_WGS84.geojson');
        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);

        // Test that the additional columns are added in the right order
        $columnNames = array_keys($rows[0]->data);
        $this->assertCount(self::DEFAULT_COLUMNS_COUNT + 2, $columnNames);
        $this->assertSame('userdata.prop1', $columnNames[self::DEFAULT_COLUMNS_COUNT]);
        $this->assertSame('userdata.prop2', $columnNames[self::DEFAULT_COLUMNS_COUNT + 1]);

        $this->api->assertCsvRow($this->getExpectedVseResultFromCSV([
            'userdata.prop1' => 'one||two',
            'userdata.prop2' => '2||4',
        ]), $rows[0]);
    }

    public function testResolverJobResultIsComputedAndFetchedAsCsvWithLV95OverlappingPolygons(): void
    {
        $entity = $this->setupJobAsCreated(__DIR__ . '/fixtures/resolving_polygon_overlapping_1_LV95.geojson');
        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);

        // Test that the additional columns are added in the right order
        $columnNames = array_keys($rows[0]->data);
        $this->assertCount(self::DEFAULT_COLUMNS_COUNT + 2, $columnNames);
        $this->assertSame('userdata.prop1', $columnNames[self::DEFAULT_COLUMNS_COUNT]);
        $this->assertSame('userdata.prop2', $columnNames[self::DEFAULT_COLUMNS_COUNT + 1]);

        $this->api->assertCsvRow($this->getExpectedVseResultFromCSV([
            'userdata.prop1' => 'one||two',
            'userdata.prop2' => '2||4',
        ]), $rows[0]);
    }

    private function createSVEBuildingEntranceData(): BuildingEntranceData
    {
        return BuildingEntranceData::create(
            countryCode: CountryCodeEnum::CH,
            buildingId: '263093336',
            entranceId: '0',
            streetId: '10030023',
            street: new Street('Feerstrasse', new StreetNumber(5, '.1')),
            streetAbbreviated: new Street('Feerstr.', new StreetNumber(5, '.1')),
            postalCode: '5000',
            locality: 'Aarau',
            municipalityCode: '4001',
            cantonCode: 'AG',
            geoCoordinateEastLV95: '2646282.092',
            geoCoordinateNorthLV95: '1249386.338',
            addressId: '102867150',
        );
    }

    /**
     * @param array<string, string> $userdata
     *
     * @return array<string, string>
     */
    private function getExpectedVseResultFromCSV(array $userdata = []): array
    {
        return [
            'country_code' => 'CH',
            'egid' => '263093336',
            'edid' => '0',
            'municipality_code' => '4001',
            'postal_code' => '5000',
            'locality' => 'Aarau',
            'street_name' => 'Feerstrasse',
            'street_house_number' => '5.1',
            'confidence' => '1',
            'match_type' => 'geoJson',
            'latitude' => '47.393677331675',
            'longitude' => '8.0516517853902',
        ] + [...$userdata];
    }

    /**
     * @param array<string, string> $userdata
     *
     * @return array<string, int|string|array<string, string>>
     */
    private function getExpectedVseResultAsJson(array $userdata = []): array
    {
        $data = [
            'confidence' => 1,
            'match_type' => 'geoJson',
            'country_code' => 'CH',
            'building_id' => '263093336',
            'entrance_id' => '0',
            'address' => [
                'municipality_code' => '4001',
                'postal_code' => '5000',
                'locality' => 'Aarau',
                'street_name' => 'Feerstrasse',
                'street_house_number' => '5.1',
                'country_code' => 'CH',
            ],
        ];

        if ([] !== $userdata) {
            $data['additional_data'] = $userdata;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $data
     */
    private function assertSameArrayRecursive(array $expected, array $data): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            if (!\is_array($value)) {
                $this->assertSame($value, $data[$key]);
                continue;
            }

            $this->assertSameArrayRecursive($value, $data[$key]);
        }
    }

    private function setupJobAsCreated(string $fixtureFile): ResolverJob
    {
        if (false === $contents = file_get_contents($fixtureFile)) {
            throw new \RuntimeException('Unable to read fixtures file for testing: ' . $fixtureFile);
        }

        /** @var BuildingEntranceImporterInterface $importer */
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([$this->createSVEBuildingEntranceData()]);

        $entity = $this->api->createJob(ResolverTypeEnum::GEO_JSON, $contents);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $entity->state, var_export($entity->failure, true));

        return $entity;
    }
}
