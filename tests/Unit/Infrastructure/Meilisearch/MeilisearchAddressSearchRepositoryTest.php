<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Meilisearch;

use App\Infrastructure\Meilisearch\Contract\IndexProviderInterface;
use App\Infrastructure\Meilisearch\MeilisearchAddressSearchRepository;
use App\Tests\Util\BuildingAddressModelBuilder;
use Meilisearch\Endpoints\Indexes;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[Small]
final class MeilisearchAddressSearchRepositoryTest extends TestCase
{
    private MockObject&IndexProviderInterface $indexProvider;

    private MeilisearchAddressSearchRepository $searchRepository;

    protected function setUp(): void
    {
        $this->indexProvider = $this->createMock(IndexProviderInterface::class);

        $this->searchRepository = new MeilisearchAddressSearchRepository(
            $this->indexProvider,
            new NullLogger(),
        );
    }

    public function testDeleteByImportedAtBefore(): void
    {
        $dateTime = new \DateTimeImmutable('2020-11-12 10:11:12');

        $indexClient = $this->mockGetBuildingEntranceIndex();
        $indexClient->expects($this->once())
            ->method('deleteDocuments')
            ->with(['filter' => 'importedAt < 20201112'])
            ->willReturn(['taskUid' => 'task-id'])
        ;

        $this->searchRepository->deleteByImportedAtBefore($dateTime);
    }

    public function testDeleteByIds(): void
    {
        $indexClient = $this->mockGetBuildingEntranceIndex();
        $indexClient->expects($this->once())
            ->method('deleteDocuments')
            ->with(['filter' => 'id IN ["123","456"]'])
            ->willReturn(['taskUid' => 'task-id'])
        ;

        $this->searchRepository->deleteByIds(['123', '456']);
    }

    public function testIndexBuildingAddresses(): void
    {
        $buildingAddress1 = BuildingAddressModelBuilder::buildBuildingAddress(BuildingAddressModelBuilder::UUID1);
        $buildingAddress2 = BuildingAddressModelBuilder::buildBuildingAddress(BuildingAddressModelBuilder::UUID2);

        $indexClient = $this->mockGetBuildingEntranceIndex();
        $indexClient->expects($this->once())
            ->method('addDocumentsNdjson')
            ->willReturnCallback(function (string $docs, string $id): array {
                $docs = explode("\n", $docs);
                $this->assertCount(2, $docs);
                $doc1 = (array) json_decode($docs[0], true, 512, \JSON_THROW_ON_ERROR);
                $this->assertArrayHasKey('id', $doc1);
                $this->assertSame(BuildingAddressModelBuilder::UUID1, $doc1['id']);
                $this->assertArrayHasKey('jsonModel', $doc1);

                $doc2 = (array) json_decode($docs[1], true, 512, \JSON_THROW_ON_ERROR);
                $this->assertArrayHasKey('id', $doc2);
                $this->assertSame(BuildingAddressModelBuilder::UUID2, $doc2['id']);
                $this->assertArrayHasKey('jsonModel', $doc2);

                $this->assertSame('id', $id);

                return ['taskUid' => 'task-id'];
            })
        ;

        foreach ($this->searchRepository->indexBuildingAddresses([$buildingAddress1, $buildingAddress2]) as $result) {
            // Loop for index to happen
        }
    }

    public function testIndexBuildingAddressesEmptyList(): void
    {
        $this->indexProvider->expects($this->never())->method('getBuildingEntranceIndex');

        foreach ($this->searchRepository->indexBuildingAddresses([]) as $result) {
            // Loop for index to happen
        }
    }

    public function testDeleteByIdsEmptyList(): void
    {
        $this->indexProvider->expects($this->never())->method('getBuildingEntranceIndex');
        $this->searchRepository->deleteByIds([]);
    }

    private function mockGetBuildingEntranceIndex(): Indexes&MockObject
    {
        $indexes = $this->createMock(Indexes::class);
        $this->indexProvider->expects($this->once())
            ->method('getBuildingEntranceIndex')
            ->willReturn($indexes)
        ;

        return $indexes;
    }
}
