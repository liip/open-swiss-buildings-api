<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\HttpFoundation;

use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeDecider;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[Small]
final class RequestContentTypeDeciderTest extends TestCase
{
    public function testMissingContentTypeReturnsNull(): void
    {
        $request = new Request();

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNull($contentType);
    }

    public function testEmptyContentTypeReturnsNull(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', '');

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNull($contentType);
    }

    public function testEmptyContentTypeWithCharsetReturnsNull(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', ';charset=utf-8');

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNull($contentType);
    }

    public function testContentTypeIsReturned(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'text/csv');

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNotNull($contentType);
        $this->assertSame('text/csv', $contentType->type);
        $this->assertNull($contentType->charset);
    }

    public function testContentTypeWithOptionsIsReturned(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'text/csv; boundary=b1');

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNotNull($contentType);
        $this->assertSame('text/csv', $contentType->type);
        $this->assertNull($contentType->charset);
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideContentTypesWithCharset(): iterable
    {
        yield ['text/csv;charset=utf-8'];
        yield ['text/csv;Charset="utf-8"'];
        yield ['text/csv; charset="utf-8"'];
        yield ['text/csv; charset=UTF-8'];
    }

    #[DataProvider('provideContentTypesWithCharset')]
    public function testContentTypeWithCharsetIsReturned(string $header): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', $header);

        $contentType = RequestContentTypeDecider::getContentType($request);

        $this->assertNotNull($contentType);
        $this->assertSame('text/csv', $contentType->type);
        $this->assertSame('utf-8', $contentType->charset);
    }

    /**
     * @return iterable<array{RequestContentTypeEnum|null, string, RequestContentTypeEnum|null}>
     */
    public static function provideAcceptHeaders(): iterable
    {
        yield [RequestContentTypeEnum::JSON, 'application/json', null];
        yield [RequestContentTypeEnum::CSV, 'text/csv', null];
        yield [null, 'image/png', null];
        yield [RequestContentTypeEnum::JSON, 'image/png', RequestContentTypeEnum::JSON];
        yield [RequestContentTypeEnum::JSON, 'image/png,application/json,text/csv', null];
        yield [RequestContentTypeEnum::WILDCARD, 'image/png,*/*', null];
    }

    #[DataProvider('provideAcceptHeaders')]
    public function testContentTypeIsNegotiated(?RequestContentTypeEnum $expected, string $header, ?RequestContentTypeEnum $fallback): void
    {
        $request = new Request();
        $request->headers->set('Accept', $header);

        $contentType = RequestContentTypeDecider::decideContentType($request, $fallback);

        $this->assertSame($expected, $contentType);
    }
}
