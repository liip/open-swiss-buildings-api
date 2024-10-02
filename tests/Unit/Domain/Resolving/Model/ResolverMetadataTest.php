<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving\Model;

use App\Domain\Resolving\Model\Job\ResolverMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ResolverMetadata::class)]
final class ResolverMetadataTest extends TestCase
{
    public function testMetadataWithAdditionalColumnsAreSorted(): void
    {
        $metadata = new ResolverMetadata();

        $additionalColumns = ['propZ', 'propB', 'propA', 'propC'];
        $metadata = $metadata->withAdditionalColumns($additionalColumns);

        $this->assertSame(
            ['propA', 'propB', 'propC', 'propZ'],
            $metadata->additionalColumns,
        );
    }

    public function testMetadataCreateAdditionalColumnsAreSorted(): void
    {
        $metadata = new ResolverMetadata(
            additionalColumns: ['propZ', 'propB', 'propA', 'propC'],
        );

        $this->assertSame(
            ['propA', 'propB', 'propC', 'propZ'],
            $metadata->additionalColumns,
        );
    }

    public function testMetadataFromArrayAdditionalColumnsAreSorted(): void
    {
        $metadata = ResolverMetadata::fromArray(['additional-columns' => 'propZ!!propB!!propA!!propC']);

        $this->assertSame(
            ['propA', 'propB', 'propC', 'propZ'],
            $metadata->additionalColumns,
        );
    }

    public function testMetadataFromArrayThrowsOnInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Additional column must be a string');

        // We ignore the argument-type, to trigger tehe above exception
        /* @phpstan-ignore argument.type */
        ResolverMetadata::fromArray(['additional-columns' => ['foo']]);
    }
}
