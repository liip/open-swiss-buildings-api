<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Tests\Util\ResolvingApi;
use PHPUnit\Framework\Attributes\Large;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Large]
final class ResolverJobMunicipalitiesCodesTest extends WebTestCase
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
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, '');
        $this->assertSame(ResolverTypeEnum::MUNICIPALITIES_CODES, $job->type);

        $jobInfo = $this->api->getJobInfo($job->id);
        $this->assertSame($job->id, $jobInfo->id);
    }

    public function testResolverJobResultCanBeFetched(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
            BuildingEntranceData::create(
                buildingId: '124',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(185)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(185)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            bfsnumber,field1
            111,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow([
            'egid' => '123',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'confidence' => '1',
            'match_type' => 'municipalityCode',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1',
        ], $rows[0]);
        $this->api->assertCsvRow([
            'egid' => '124',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '185',
            'confidence' => '1',
            'match_type' => 'municipalityCode',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1',
        ], $rows[1]);
    }

    public function testOrderOfColumnsDoesNotMatter(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            field1,bfsnumber,field2
            value1,111,value2
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow([
            'egid' => '123',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'confidence' => '1',
            'match_type' => 'municipalityCode',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1',
            'userdata.field2' => 'value2',
        ], $rows[0]);
    }

    public function testDuplicatesAreResolved(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            bfsnumber,field1
            111,value1
            111,value2
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow([
            'egid' => '123',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'confidence' => '1',
            'match_type' => 'municipalityCode',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1||value2',
        ], $rows[0]);
    }

    public function testAdditionalDataHasNoDuplicates(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            bfsnumber,field1
            111,value1
            111,value2
            111,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow([
            'egid' => '123',
            'edid' => '0',
            'municipality_code' => '111',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'confidence' => '1',
            'match_type' => 'municipalityCode',
            'latitude' => '32.124506172887',
            'longitude' => '-19.91799349601',
            'userdata.field1' => 'value1||value2',
        ], $rows[0]);
    }

    public function testNonResolvableEntryGivesEmptyResult(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceData::create(
                buildingId: '123',
                entranceId: '0',
                streetId: '7',
                street: new Street('Limmatstrasse', new StreetNumber(183)),
                streetAbbreviated: new Street('Limmatstr', new StreetNumber(183)),
                cantonCode: 'ZH',
                postalCode: '8005',
                locality: 'Zürich',
                municipalityCode: '111',
                geoCoordinateEastLV95: '1',
                geoCoordinateNorthLV95: '2',
            ),
        ]);

        $content = <<<'EOF'
            bfsnumber,field1
            222,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::MUNICIPALITIES_CODES, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
    }
}
