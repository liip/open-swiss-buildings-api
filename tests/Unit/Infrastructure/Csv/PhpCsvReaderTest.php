<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Csv;

use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Model\CsvRow;
use App\Infrastructure\Csv\PhpCsvReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class PhpCsvReaderTest extends TestCase
{
    public function testCsvReaderDoesNotAcceptWrongDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('delimiter');
        new PhpCsvReader($this->createResource([]), '||');
    }

    public function testCsvReaderDoesNotAcceptWrongEnclosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('enclosure');
        new PhpCsvReader($this->createResource([]), null, '**');
    }

    public function testClosedResourceThrowsException(): void
    {
        $resource = $this->createResource([]);
        fclose($resource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('closed');
        new PhpCsvReader($resource, ',', '"');
    }

    public function testInvalidCsvThrowsException(): void
    {
        $resource = $this->createResource([]);

        // Move to the end, so that no data can be read
        fseek($resource, 100);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('No header row');
        $reader->getHeader();
    }

    public function testEmptyCsvThrowsException(): void
    {
        $resource = $this->createResource([]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('No header row');
        $reader->getHeader();
    }

    public function testEmptyHeaderRowThrowsException(): void
    {
        $resource = $this->createResource([
            '',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('Empty header row');
        $reader->getHeader();
    }

    public function testEmptyHeaderThrowsException(): void
    {
        $resource = $this->createResource([
            'field1,',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('Empty header row');
        $reader->getHeader();
    }

    public function testHeaderIsReturned(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1,value2',
            'value3,value4',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->assertSame(['field1', 'field2'], $reader->getHeader());
    }

    /**
     * @param non-empty-array<string> $lines
     * @param non-empty-string        $delimiter
     */
    #[DataProvider('provideDelimiterCanBeGuessedCases')]
    public function testDelimiterCanBeGuessed(array $lines, string $delimiter): void
    {
        $resource = $this->createResource($lines);

        $reader = new PhpCsvReader($resource);

        $this->assertSame($delimiter, $reader->getDelimiter());
    }

    /**
     * @return iterable<string, array{non-empty-array<string>, non-empty-string}>
     */
    public static function provideDelimiterCanBeGuessedCases(): iterable
    {
        yield 'delimiter: ,' => [['field1,field2'], ','];
        yield 'delimiter: ;' => [['field1;field2'], ';'];
        yield 'delimiter: tab' => [["field1\tfield2"], "\t"];
        yield 'delimiter: default' => [['field1'], ','];
    }

    /**
     * @param non-empty-array<string> $lines
     * @param non-empty-string        $enclosure
     */
    #[DataProvider('provideEnclosureCanBeGuessedCases')]
    public function testEnclosureCanBeGuessed(array $lines, string $enclosure): void
    {
        $resource = $this->createResource($lines);

        $reader = new PhpCsvReader($resource);

        $this->assertSame($enclosure, $reader->getEnclosure());
    }

    /**
     * @return iterable<string, array{non-empty-array<string>, non-empty-string}>
     */
    public static function provideEnclosureCanBeGuessedCases(): iterable
    {
        yield 'enclosure: "' => [['"field 1","field 2"'], '"'];
        yield "delimiter: '" => [["'field 1','field 2'"], "'"];
        yield 'delimiter: default' => [['field1'], '"'];
    }

    public function testDataCanBeRead(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1,value2',
            'value3,value4',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $rows = [];
        foreach ($reader->read() as $row) {
            $rows[] = $row;
        }

        $this->assertCount(2, $rows);
        $this->assertRow(2, ['field1' => 'value1', 'field2' => 'value2'], $rows[0]);
        $this->assertRow(3, ['field1' => 'value3', 'field2' => 'value4'], $rows[1]);
    }

    public function testInvalidRowThrowsException(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('Could not read row');
        foreach ($reader->read() as $row) {
            // Loop for exception to happen
        }
    }

    public function testEmptyRowThrowsException(): void
    {
        $resource = $this->createResource([
            'field1',
            '',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('is empty');
        foreach ($reader->read() as $row) {
            // Loop for exception to happen
        }
    }

    public function testInvalidRowInMemoryThrowsException(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1,value2',
            'value3',
            'value5,value6',
            'value7,value8',
        ]);

        // Guessing the delimiter and enclosure reads first rows into memory
        $reader = new PhpCsvReader($resource);

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('Could not read row');
        foreach ($reader->read() as $row) {
            // Loop for exception to happen
        }
    }

    public function testEmptyRowInMemoryThrowsException(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1,value2',
            '',
            'value5,value6',
            'value7,value8',
        ]);

        // Guessing the delimiter and enclosure reads first rows into memory
        $reader = new PhpCsvReader($resource);

        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('is empty');
        foreach ($reader->read() as $row) {
            // Loop for exception to happen
        }
    }

    public function testDataCanBeReadWithRowsInMemory(): void
    {
        $resource = $this->createResource([
            'field1,field2',
            'value1,value2',
            'value3,value4',
            'value5,value6',
            'value7,value8',
        ]);

        // Guessing the delimiter and enclosure reads first rows into memory
        $reader = new PhpCsvReader($resource);

        $rows = [];
        foreach ($reader->read() as $row) {
            $rows[] = $row;
        }

        $this->assertCount(4, $rows);
        $this->assertRow(2, ['field1' => 'value1', 'field2' => 'value2'], $rows[0]);
        $this->assertRow(3, ['field1' => 'value3', 'field2' => 'value4'], $rows[1]);
        $this->assertRow(4, ['field1' => 'value5', 'field2' => 'value6'], $rows[2]);
        $this->assertRow(5, ['field1' => 'value7', 'field2' => 'value8'], $rows[3]);
    }

    #[DataProvider('provideBomIsRemovedCases')]
    public function testBomIsRemoved(string $bom): void
    {
        $resource = $this->createResource([
            "{$bom}field1,field2",
            'value1,value2',
            'value3,value4',
        ]);

        $reader = new PhpCsvReader($resource, ',', '"');

        $rows = [];
        foreach ($reader->read() as $row) {
            $rows[] = $row;
        }

        $this->assertSame(['field1', 'field2'], $reader->getHeader());
        $this->assertCount(2, $rows);
        $this->assertRow(2, ['field1' => 'value1', 'field2' => 'value2'], $rows[0]);
        $this->assertRow(3, ['field1' => 'value3', 'field2' => 'value4'], $rows[1]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideBomIsRemovedCases(): iterable
    {
        yield 'utf-32-le' => ["\xFF\xFE\x00\x00"];
        yield 'utf-32-be' => ["\x00\x00\xFE\xFF"];
        yield 'utf-16-le' => ["\xFE\xFF"];
        yield 'utf-16-be' => ["\xFF\xFE"];
        yield 'utf-8' => ["\xEF\xBB\xBF"];
    }

    /**
     * @param non-empty-string|null $charset
     * @param string[]              $lines
     */
    #[DataProvider('provideDataCanBeReadInDifferentCharsetCases')]
    public function testDataCanBeReadInDifferentCharset(?string $charset, array $lines): void
    {
        $resource = $this->createResource($lines);

        $reader = new PhpCsvReader($resource, ',', '"', $charset);

        $rows = [];
        foreach ($reader->read() as $row) {
            $rows[] = $row;
        }

        $this->assertCount(1, $rows);
        $this->assertRow(2, ['field¹' => 'valüe1', 'field2' => 'value²'], $rows[0]);
    }

    /**
     * @return array<array{non-empty-string|null, string[]}>
     */
    public static function provideDataCanBeReadInDifferentCharsetCases(): iterable
    {
        $buildLinesInCharset = static fn(string $charset): array => [
            mb_convert_encoding('field¹,field2', $charset, 'UTF-8'),
            mb_convert_encoding('valüe1,value²', $charset, 'UTF-8'),
        ];

        yield ['utf-8', $buildLinesInCharset('utf-8')];
        yield ['iso-8859-1', $buildLinesInCharset('iso-8859-1')];
        yield ['windows-1252', $buildLinesInCharset('windows-1252')];

        // Handle cases where no default charset is provided
        yield [null, $buildLinesInCharset('utf-8')];
        yield [null, $buildLinesInCharset('iso-8859-1')];
        yield [null, $buildLinesInCharset('windows-1252')];
    }

    /**
     * @param string[] $lines
     *
     * @return resource
     */
    private function createResource(array $lines)
    {
        $resource = fopen('php://memory', 'r+');
        $this->assertIsResource($resource);

        foreach ($lines as $line) {
            fwrite($resource, "{$line}\n");
        }
        rewind($resource);

        return $resource;
    }

    /**
     * @param array<string, string> $expectedData
     */
    private function assertRow(int $expectedRowNumber, array $expectedData, CsvRow $actualRow): void
    {
        $this->assertSame($expectedRowNumber, $actualRow->number);
        $this->assertSame($expectedData, $actualRow->data);
    }
}
