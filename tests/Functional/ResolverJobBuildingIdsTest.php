<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Tests\Util\BuildingEntranceDataModelBuilder;
use App\Tests\Util\ResolvingApi;
use PHPUnit\Framework\Attributes\Large;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Large]
final class ResolverJobBuildingIdsTest extends WebTestCase
{
    private ResolvingApi $api;

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
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, '');
        $this->assertSame(ResolverTypeEnum::BUILDING_IDS, $job->type);

        $jobInfo = $this->api->getJobInfo($job->id);
        $this->assertSame($job->id, $jobInfo->id);
    }

    public function testResolverJobResultCanBeFetched(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                countryCode: CountryCodeEnum::CH,
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                cantonCode: 'ZH',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            egid,field1
            123,value1
            EOF;
        $entity = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $entity->state, var_export($entity->failure, true));

        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow([
            'egid' => '123',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'country_code' => 'CH',
            'confidence' => '1',
            'match_type' => 'buildingId',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1',
        ], $rows[0]);
    }

    public function testResolverJobResultAreOnlyInCH(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::LI),
        ]);

        $content = <<<'EOF'
            egid,field1
            2366055,value1
            EOF;
        $entity = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $entity->state, var_export($entity->failure, true));

        $rows = $this->api->getJobResults($entity->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
    }

    public function testOrderOfColumnsDoesNotMatter(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
        ]);

        $content = <<<'EOF'
            field1,egid,field2
            value1,2366055,value2
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1',
                'userdata.field2' => 'value2',
            ]),
            $rows[0],
        );
    }

    public function testDuplicatesAreResolved(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
        ]);

        $content = <<<'EOF'
            egid,field1
            2366055,value1
            2366055,value2
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1||value2',
            ]),
            $rows[0],
        );
    }

    public function testAdditionalDataHasNoDuplicates(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
        ]);

        $content = <<<'EOF'
            egid,field1
            2366055,value1
            2366055,value2
            2366055,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1||value2',
            ]),
            $rows[0],
        );
    }

    public function testNonResolvableEntryShowsInResults(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
        ]);

        $content = <<<'EOF'
            egid,field1
            2366055,value1
            9999999,value2
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createEmptyBuildingResult([
                'egid' => '9999999',
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value2',
            ]),
            $rows[1],
        );
    }

    public function testEmptyBuildingIdResultsInUnresolvableEntry(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(CountryCodeEnum::CH),
        ]);

        $content = <<<'EOF'
            egid,field1
            ,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::BUILDING_IDS, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createEmptyBuildingResult([
                'confidence' => '1',
                'match_type' => 'buildingId',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
    }
}
