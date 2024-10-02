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
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow(
            $this->createLiipBuildingResult([
                'original_address' => 'Limmatstrasse 183, 8005 Zürich',
                'confidence' => '1',
                'match_type' => 'exact',
            ]),
            $rows[0],
        );
    }

    public function testEmptyStreetHouseNumberMatchesExactly(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_house_number.csv',
            [$this->createLiipBuildingEntranceData(streetNumber: null)],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '',
            'original_address' => 'Limmatstrasse, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
        ]), $rows[0]);
    }

    public function testEmptyZipCodeResultsInUnresolvableEntry(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_zip_code.csv',
            [$this->createLiipBuildingEntranceData()],
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
            [$this->createLiipBuildingEntranceData()],
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
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstr 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
        ]), $rows[0]);
    }

    public function testAddressIsMatchedIgnoringCase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_casechange.csv',
            [$this->createLiipBuildingEntranceData(streetNumberSuffix: 'a')],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'limmatStrasse 183A, 8005 züRich',
            'confidence' => '0.99',
            'match_type' => 'exactNormalized',
        ]), $rows[0]);
    }

    public function testOrderOfColumnsDoesNotMatter(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_column_shuffle.csv',
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
            'userdata.field2' => 'value2',
        ]), $rows[0]);
    }

    public function testDuplicatesAreResolved(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_duplicated.csv',
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
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
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
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
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '1',
            'match_type' => 'exact',
        ]), $rows[0]);
    }

    public function testAddressMatchesOnSameStreet(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            $this->createLiipBuildingEntranceData(),
            $this->createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            $this->createLiipBuildingEntranceData(buildingId: '2366056', streetId: '10004213', postalCode: '8001'),
            $this->createLiipBuildingEntranceData(buildingId: '2366057', streetId: '10004214', postalCode: '8006', streetNumber: 1),
            $this->createLiipBuildingEntranceData(buildingId: '2366058', postalCode: '8006', streetNumber: 2),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8006,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.99',
            'match_type' => 'streetExact-full',
        ]), $rows[0]);
    }

    public function testHouseNumberSuffixMatchesWithoutSuffix(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_suffix.csv',
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 183a, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetExact-houseNumberWithoutSuffix',
        ]), $rows[0]);
    }

    public function testHouseNumberSuffixMatchesWithOtherSuffix(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_suffix.csv',
            [$this->createLiipBuildingEntranceData(streetNumberSuffix: '.1')],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183.1',
            'original_address' => 'Limmatstrasse 183a, 8005 Zürich',
            'confidence' => '0.96',
            'match_type' => 'streetExact-houseNumbersWithOtherSuffix',
        ]), $rows[0]);
    }

    public function testHouseNumberWithoutSuffixMatchesOnSuffixes(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            $this->createLiipBuildingEntranceData(entranceId: '0', streetNumberSuffix: 'a'),
            $this->createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            $this->createLiipBuildingEntranceData(buildingId: '2366056', streetNumber: 1831),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8005,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
        ]), $rows[0]);
        $this->api->assertCsvRow([
            'street_house_number' => '183b',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
        ], $rows[1]);
    }

    public function testHouseNumberMatchesOnlyOnSameStreet(): void
    {
        $importer = self::getContainer()->get(BuildingEntranceImporterInterface::class);
        $importer->importManualBuildingData([
            $this->createLiipBuildingEntranceData(entranceId: '0', streetNumberSuffix: 'a'),
            $this->createLiipBuildingEntranceData(entranceId: '1', streetNumberSuffix: 'b'),
            $this->createLiipBuildingEntranceData(buildingId: '2366056', streetId: '10004213', postalCode: '8001', streetNumberSuffix: 'c'),
            $this->createLiipBuildingEntranceData(buildingId: '2366057', postalCode: '8006', streetNumber: 1),
        ]);

        $content = <<<'EOF'
            street_housenumbers,swisszipcode,town,field1
            Limmatstrasse 183,8006,Zürich,value1
            EOF;
        $job = $this->api->createJob(ResolverTypeEnum::ADDRESS_SEARCH, $content);
        $this->assertSame(ResolverJobStateEnum::COMPLETED, $job->state, var_export($job->failure, true));

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);

        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183a',
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
        ]), $rows[0]);

        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'edid' => '1',
            'street_house_number' => '183b',
            'original_address' => 'Limmatstrasse 183, 8006 Zürich',
            'confidence' => '0.97',
            'match_type' => 'streetExact-houseNumbersWithSuffix',
        ]), $rows[1]);
    }

    public function testClosestHouseNumberIsMatched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                $this->createLiipBuildingEntranceData(entranceId: '1', streetNumber: 120),
                $this->createLiipBuildingEntranceData(entranceId: '2', streetNumber: 181),
                $this->createLiipBuildingEntranceData(entranceId: '3', streetNumber: 190),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'edid' => '2',
            'street_house_number' => '181',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.8',
            'match_type' => 'streetExact-closestHouseNumber',
        ]), $rows[0]);
    }

    public function testClosestFarAwayHouseNumberIsMatched(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                $this->createLiipBuildingEntranceData(entranceId: '1', streetNumber: 1),
                $this->createLiipBuildingEntranceData(entranceId: '2', streetNumber: 250),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'edid' => '2',
            'street_house_number' => '250',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.5',
            'match_type' => 'streetExact-closestHouseNumber',
        ]), $rows[0]);
    }

    public function testClosestHouseNumberIsMatchedOnlyOnSameStreet(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip.csv',
            [
                $this->createLiipBuildingEntranceData(entranceId: '0', streetId: '10004999', postalCode: '8001'),
                $this->createLiipBuildingEntranceData(entranceId: '1', streetNumber: 185),
                $this->createLiipBuildingEntranceData(entranceId: '2', streetNumber: 250),
            ],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);

        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'edid' => '1',
            'street_house_number' => '185',
            'original_address' => 'Limmatstrasse 183, 8005 Zürich',
            'confidence' => '0.8',
            'match_type' => 'streetExact-closestHouseNumber',
        ]), $rows[0]);
    }

    public function testEmptyStreetHouseNumberMatchesClosestHouseNumber(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_empty_house_number.csv',
            [$this->createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse, 8005 Zürich',
            'confidence' => '0.4',
            'match_type' => 'streetExact-closestHouseNumber',
        ]), $rows[0]);
    }

    public function testZeroStreetHouseNumberMatchesClosestHouseNumber(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_zero_house_number.csv',
            [$this->createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 0, 8005 Zürich',
            'confidence' => '0.4',
            'match_type' => 'streetExact-closestHouseNumber',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberRanges(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_range.csv',
            [$this->createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'original_address' => 'Limmatstrasse 180-185, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberRange',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRanges(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range.csv',
            [$this->createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183A-g, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRangesUppercase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range_uppercase.csv',
            [$this->createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183A-G, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
        ]), $rows[0]);
    }

    public function testStreetHouseNumberSuffixRangesLowercase(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_with_number_suffix_range_lowercase.csv',
            [$this->createLiipBuildingEntranceData(streetNumberSuffix: 'c')],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
            'street_house_number' => '183c',
            'original_address' => 'Limmatstrasse 183a-g, 8005 Zürich',
            'confidence' => '0.98',
            'match_type' => 'streetNumberSuffixRange',
        ]), $rows[0]);
    }

    public function testAdditionalDataHasNoDuplicates(): void
    {
        $job = $this->setupJobAsCreated(
            __DIR__ . '/fixtures/resolving_address_liip_duplicated.csv',
            [$this->createLiipBuildingEntranceData()],
        );
        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(1, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
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
            [$this->createLiipBuildingEntranceData()],
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
            [$this->createLiipBuildingEntranceData()],
        );

        $rows = $this->api->getJobResults($job->id);
        $this->assertCount(2, $rows);
        $this->api->assertCsvRow($this->createLiipBuildingResult([
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
    private function createLiipBuildingResult(array $defaultOverride): array
    {
        return array_merge([
            'egid' => '2366055',
            'edid' => '0',
            'municipality_code' => '261',
            'postal_code' => '8005',
            'locality' => 'Zürich',
            'street_name' => 'Limmatstrasse',
            'street_house_number' => '183',
            'original_address' => '',
            'confidence' => '',
            'match_type' => '',
            'latitude' => '47.386170922358',
            'longitude' => '8.5292387777084',
            'userdata.field1' => 'value1',
        ], $defaultOverride);
    }

    /**
     * @param array<string, string> $defaultOverride
     *
     * @return array<string, string>
     */
    private function createEmptyBuildingResult(array $defaultOverride): array
    {
        return array_merge([
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

    /**
     * @param positive-int|null     $streetNumber
     * @param non-empty-string|null $streetNumberSuffix
     */
    private function createLiipBuildingEntranceData(
        string $entranceId = '0',
        string $buildingId = '2366055',
        string $streetId = '10004212',
        string $postalCode = '8005',
        ?int $streetNumber = 183,
        ?string $streetNumberSuffix = null,
    ): BuildingEntranceData {
        $streetNr = null;
        if (null !== $streetNumber || null !== $streetNumberSuffix) {
            $streetNr = new StreetNumber($streetNumber, $streetNumberSuffix);
        }

        return BuildingEntranceData::create(
            buildingId: $buildingId,
            entranceId: $entranceId,
            streetId: $streetId,
            street: new Street('Limmatstrasse', $streetNr),
            streetAbbreviated: new Street('Limmatstr', $streetNr),
            postalCode: $postalCode,
            locality: 'Zürich',
            municipalityCode: '261',
            cantonCode: 'ZH',
            geoCoordinateEastLV95: '2682348.561',
            geoCoordinateNorthLV95: '1248943.136',
        );
    }
}
