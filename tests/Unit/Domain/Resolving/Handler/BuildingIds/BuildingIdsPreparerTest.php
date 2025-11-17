<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving\Handler\BuildingIds;

use App\Domain\Resolving\Contract\CsvReaderFactoryInterface;
use App\Domain\Resolving\Contract\CsvReaderInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Handler\BuildingIds\BuildingIdsPreparer;
use App\Domain\Resolving\Model\CsvRow;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Tests\Util\MemoryTaskRepository;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

#[Small]
final class BuildingIdsPreparerTest extends TestCase
{
    private MemoryTaskRepository $taskRepository;
    private ResolverMetadataWriteRepositoryInterface&MockObject $metadataRepository;
    private CsvReaderInterface&Stub $csvReader;
    private BuildingIdsPreparer $preparer;

    protected function setUp(): void
    {
        $this->taskRepository = new MemoryTaskRepository();
        $this->metadataRepository = $this->createMock(ResolverMetadataWriteRepositoryInterface::class);
        $this->csvReader = $this->createStub(CsvReaderInterface::class);
        $csvReaderFactory = $this->createStub(CsvReaderFactoryInterface::class);
        $csvReaderFactory->method('createReader')->willReturn($this->csvReader);

        $this->preparer = new BuildingIdsPreparer(
            $this->taskRepository,
            $this->metadataRepository,
            $csvReaderFactory,
            new NullLogger(),
        );
    }

    public function testDataCanBeParsed(): void
    {
        $data = $this->createData([
            'egid',
            '123',
            '456',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn(',');
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['egid']);
        $this->csvReader->method('read')->willReturn([
            new CsvRow(2, ['egid' => '123']),
            new CsvRow(3, ['egid' => '456']),
        ]);

        $this->preparer->prepareJob($data);

        $tasks = $this->taskRepository->tasks;
        $this->assertCount(2, $tasks);
        $this->assertSame('123', $tasks[0]->matchingBuildingId);
        $this->assertSame('456', $tasks[1]->matchingBuildingId);
    }

    public function testEmptyBuildingIdIsSupported(): void
    {
        $data = $this->createData([
            'egid,field1',
            ',value1',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn("\t");
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['egid', 'field1']);
        $this->csvReader->method('read')->willReturn([
            new CsvRow(2, ['egid' => '', 'field1' => 'value1']),
        ]);

        $this->preparer->prepareJob($data);

        $tasks = $this->taskRepository->tasks;
        $this->assertCount(1, $tasks);
        $this->assertSame('', $tasks[0]->matchingBuildingId);
    }

    public function testDataWithAdditionalColumnsCanBeParsed(): void
    {
        $data = $this->createData([
            'egid,field1',
            '123,value1',
            '456,value2',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn(',');
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['egid', 'field1']);
        $this->csvReader->method('read')->willReturn([
            new CsvRow(2, ['egid' => '123', 'field1' => 'value1']),
            new CsvRow(3, ['egid' => '456', 'field1' => 'value2']),
        ]);

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->with(
                $this->isInstanceOf(Uuid::class),
                $this->callback(function (ResolverMetadata $metadata): bool {
                    $this->assertSame($metadata->additionalColumns, ['field1']);

                    return true;
                }),
            )
        ;

        $this->preparer->prepareJob($data);

        $tasks = $this->taskRepository->tasks;
        $this->assertCount(2, $tasks);
        $this->assertSame('123', $tasks[0]->matchingBuildingId);
        $this->assertSame([['field1' => 'value1']], $tasks[0]->additionalData->getAsList());
        $this->assertSame('456', $tasks[1]->matchingBuildingId);
        $this->assertSame([['field1' => 'value2']], $tasks[1]->additionalData->getAsList());
    }

    public function testDelimiterIsUpdated(): void
    {
        $data = $this->createData([
            "egid\tfield1",
            "123\tvalue1",
        ]);

        $this->csvReader->method('getDelimiter')->willReturn("\t");
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['egid', 'field1']);
        $this->csvReader->method('read')->willReturn([
            new CsvRow(2, ['egid' => '123', 'field1' => 'value1']),
        ]);

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->with(
                $this->isInstanceOf(Uuid::class),
                $this->callback(function (ResolverMetadata $metadata): bool {
                    $this->assertSame("\t", $metadata->csvDelimiter);

                    return true;
                }),
            )
        ;

        $this->preparer->prepareJob($data);

        $tasks = $this->taskRepository->tasks;
        $this->assertCount(1, $tasks);
    }

    public function testEnclosureIsUpdated(): void
    {
        $data = $this->createData([
            'egid,field1',
            "123,'value 1'",
        ]);

        $this->csvReader->method('getDelimiter')->willReturn(',');
        $this->csvReader->method('getEnclosure')->willReturn("'");
        $this->csvReader->method('getHeader')->willReturn(['egid', 'field1']);
        $this->csvReader->method('read')->willReturn([
            new CsvRow(2, ['egid' => '123', 'field1' => 'value1']),
        ]);

        $this->metadataRepository->expects($this->once())
            ->method('updateMetadata')
            ->with(
                $this->isInstanceOf(Uuid::class),
                $this->callback(function (ResolverMetadata $metadata): bool {
                    $this->assertSame("'", $metadata->csvEnclosure);

                    return true;
                }),
            )
        ;

        $this->preparer->prepareJob($data);

        $tasks = $this->taskRepository->tasks;
        $this->assertCount(1, $tasks);
    }

    public function testWrongHeaderThrowsException(): void
    {
        $data = $this->createData([
            'field1,field2',
            '123,value1',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn(',');
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willThrowException(new CsvReadException('Invalid header'));

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid header');
        $this->preparer->prepareJob($data);
    }

    public function testMissingHeaderThrowsException(): void
    {
        $data = $this->createData([
            'field1,field2',
            '123,value1',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn(',');
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['field1', 'field2']);

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Header column "egid" is required');
        $this->preparer->prepareJob($data);
    }

    public function testReadingErrorThrowsException(): void
    {
        $data = $this->createData([
            'egid,field1',
            '123,value1',
        ]);

        $this->csvReader->method('getDelimiter')->willReturn("\t");
        $this->csvReader->method('getEnclosure')->willReturn('"');
        $this->csvReader->method('getHeader')->willReturn(['egid', 'field1']);
        $this->csvReader->method('read')->willThrowException(new CsvReadException('Invalid data'));

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('Invalid data');
        $this->preparer->prepareJob($data);
    }

    /**
     * @param string[] $lines
     */
    private function createData(array $lines): ResolverJobRawData
    {
        $rawData = fopen('php://memory', 'r+');
        $this->assertIsResource($rawData);

        foreach ($lines as $line) {
            fwrite($rawData, "{$line}\n");
        }
        rewind($rawData);

        return new ResolverJobRawData(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS, $rawData, new ResolverMetadata());
    }
}
