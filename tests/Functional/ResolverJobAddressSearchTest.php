<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Domain\BuildingData\Model\BuildingEntranceData;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Tests\Util\BuildingEntranceDataModelBuilder;
use App\Tests\Util\ResolvingApi;
use PHPUnit\Framework\Attributes\Large;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Large]
final class ResolverJobAddressSearchTest extends WebTestCase
{
    private ResolvingApi $api;

    protected function setUp(): void
    {
        $this->api = new ResolvingApi(self::createClient(), self::getContainer());
    }

    protected function tearDown(): void
    {
        $this->api->tearDown();
        parent::tearDown();
    }

    public function testResolverJobIsCreated(): void
    {
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, '');
        $this->assertSame(ResolverTypeEnum::ADDRESS_SEARCH, $job->type);

        $jobInfo = $this->api->getJobInfo($job->id);
        $this->assertSame($job->id, $jobInfo->id);
    }

    public function testResolverJobResultCanBeFetched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'original_address' => 'Limmatstrasse 183, 8005 Zürich',
                'confidence' => '1',
                'match_type' => 'exact',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
    }

    public function testResolverJobResultAreOnlyInCH(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(countryCode: CountryCodeEnum::CH),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(countryCode: CountryCodeEnum::LI),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'original_address' => 'Limmatstrasse 183, 8005 Zürich',
                'confidence' => '1',
                'match_type' => 'exact',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
    }

    public function testEmptyStreetHouseNumberMatchesExactly(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_house_number.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumber: null)],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            BuildingEntranceDataModelBuilder::createLiipBuildingResult([
                'street_house_number' => '',
                'original_address' => 'Limmatstrasse, 8005 Zürich',
                'confidence' => '1',
                'match_type' => 'exact',
                'userdata.field1' => 'value1',
            ]),
            $rows[0],
        );
    }

    public function testEmptyZipCodeResultsInUnresolvableEntry(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_zip_code.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createEmptyBuildingResult([
            'original_address' => 'Limmatstrasse 183,  Zürich',
        ]), $rows[0]);
    }

    public function testEmptyLocalityResultsInUnresolvableEntry(): void
    {
        $job = $this->setupJob(
            __DIR__ . '/fixtures/resolving_address_empty_locality.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createEmptyBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 ',
        ]), $rows[0]);
    }

    public function testStreetAbbreviationIsMatched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_abbreviated.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstr 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testAddressIsMatchedIgnoringCase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_casechange.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumberSuffix: 'a')],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'limmatStrasse 183A, 8005 züRich',
            'confidence' => '0.99',
            'match_type' => 'exactNormalized',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testOrderOfColumnsDoesNotMatter(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_column_shuffle.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field1' => 'value1',
            'userdata.field2' => 'value2',
        ]), $rows[0]);
    }

    public function testDuplicatesAreResolved(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_duplicated.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field1' => 'value1||value2',
        ]), $rows[0]);
    }

    public function testDuplicateResultsAreResolved(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_duplicated_results.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183a||Limmatstrasse 183b, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetExact-houseNumberWithoutSuffix',
            'userdata.field1' => 'value1||value2',
        ]), $rows[0]);
    }

    public function testAddressValuesAreCleaned(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_unclean_values.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testAddressMatchesOnSameStreet(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366056', streetId: '10004213', postalCode: '8001'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366057', streetId: '10004214', postalCode: '8006', streetNumber: 1),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366058', postalCode: '8006', streetNumber: 2),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8006,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.99',
            'match_type' => 'streetExact-full',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testHouseNumberSuffixMatchesWithoutSuffix(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_suffix.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183a, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetExact-houseNumberWithoutSuffix',
        ]), $rows[0]);
    }

    public function testHouseNumberSuffixMatchesWithOtherSuffix(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_suffix.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumberSuffix: '.1')],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183.1',
            'original_address' => 'Limmatstrasse 183a, 8005 Zürich',
            'confidence' => '0.96',
            'match_type' => 'streetExact-houseNumbersWithOtherSuffix',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testHouseNumberWithoutSuffixMatchesOnSuffixes(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '0', streetNumberSuffix: 'a'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366056', streetNumber: 1831),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8005,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
        $this->api->assertCsvRow([
            'street_house_number' => '183b',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
            'userdata.field1' => 'value1',
        ], $rows[1]);
    }

    public function testHouseNumberMatchesOnlyOnSameStreet(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '0', streetNumberSuffix: 'a'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366056', streetId: '10004213', postalCode: '8001', streetNumberSuffix: 'c'),
            BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(buildingId: '2366057', postalCode: '8006', streetNumber: 1),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8006,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);

        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
            'userdata.field1' => 'value1',
        ]), $rows[0]);

        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'edid' => '1',
            'street_house_number' => '183b',
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
            'userdata.field1' => 'value1',
        ]), $rows[1]);
    }

    public function testClosestHouseNumberIsMatched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumber: 120),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '2', streetNumber: 181),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '3', streetNumber: 190),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'edid' => '2',
            'street_house_number' => '181',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.8',
            'match_type' => 'streetExact-closestHouseNumber',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testClosestFarAwayHouseNumberIsMatched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumber: 1),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '2', streetNumber: 250),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'edid' => '2',
            'street_house_number' => '250',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.5',
            'match_type' => 'streetExact-closestHouseNumber',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testClosestHouseNumberIsMatchedOnlyOnSameStreet(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '0', streetId: '10004999', postalCode: '8001'),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '1', streetNumber: 185),
                BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(entranceId: '2', streetNumber: 250),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'edid' => '1',
            'street_house_number' => '185',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.8',
            'match_type' => 'streetExact-closestHouseNumber',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testEmptyStreetHouseNumberMatchesClosestHouseNumber(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_house_number.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse, 8005 Zürich',
            'confidence' => '0.4',
            'match_type' => 'streetExact-closestHouseNumber',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testZeroStreetHouseNumberMatchesClosestHouseNumber(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_zero_house_number.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 0, 8005 Zürich',
            'confidence' => '0.4',
            'match_type' => 'streetExact-closestHouseNumber',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberRanges(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_range.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 180-185, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberRange',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRanges(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183A-g, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRangesUppercase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range_uppercase.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183A-G, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRangesLowercase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range_lowercase.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183a-g, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
            'userdata.field1' => 'value1',
        ]), $rows[0]);
    }

    public function testAdditionalDataHasNoDuplicates(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_duplicated.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field1' => 'value1||value2',
        ]), $rows[0]);
    }

    public function testEmptyStreetResultsInUnresolvableEntry(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createEmptyBuildingResult([
            'original_address' => ', 8005 Zürich',
        ]), $rows[0]);
    }

    public function testNonResolvableEntryShowsInResults(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_plus_non_resolvable_entry.csv',
            [BuildingEntranceDataModelBuilder::createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow(BuildingEntranceDataModelBuilder::createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
        ]), $rows[0]);
        $this->api->assertCsvRow($this->createEmptyBuildingResult([
            'original_address' => 'Keine Strasse 1, 8005 Zürich',
            'userdata.field1' => 'value2',
        ]), $rows[1]);
    }

    /**
     * @param BuildingEntranceData[] $buildingData
     */
    private function setupJobAsCreated(string $fixtureFile, array $buildingData): ResolverJob
    {
        $entity = $this->setupJob($fixtureFile, $buildingData);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $entity->state, var_export($entity->failure, true));

        return $entity;
    }

    /**
     * @param BuildingEntranceData[] $buildingData
     */
    private function setupJob(string $fixtureFile, array $buildingData): ResolverJob
    {
        if (false === $contents = file_get_contents($fixtureFile)) {
            throw new \RuntimeException('Unable to read fixtures file for testing: ' . $fixtureFile);
        }

        /** @var BuildingEntranceImporterInterface $importer */
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData($buildingData);

        return $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $contents);
    }

    /**
     * @param array<string, string> $defaultOverride
     *
     * @return array<string, string>
     */
    private function createEmptyBuildingResult(array $defaultOverride): array
    {
        return array_merge([
            'country_code' => '',
            'egid' => '',
            'edid' => '',
            'municipality_code' => '',
            'postal_code' => '',
            'locality' => '',
            'street_name' => '',
            'street_house_number' => '',
            'original_address' => '',
            'confidence' => '0',
            'match_type' => 'nothing',
            'latitude' => '',
            'longitude' => '',
            'userdata.field1' => 'value1',
        ], $defaultOverride);
    }
}
