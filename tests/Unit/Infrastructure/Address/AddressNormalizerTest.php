<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Address;

use App\Infrastructure\Address\AddressNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Small]
final class AddressNormalizerTest extends TestCase
{
    private readonly AddressNormalizer $addressNormalizer;

    protected function setUp(): void
    {
        $this->addressNormalizer = new AddressNormalizer(new AsciiSlugger('de'));
    }

    #[DataProvider('provideLocalityNormalizationCases')]
    public function testLocalityNormalization(string $expected, string $locality, string $municipality, string $cantonCode): void
    {
        $this->assertSame($expected, $this->addressNormalizer->normalizeLocality($locality, $municipality, $cantonCode));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideLocalityNormalizationCases(): iterable
    {
        yield ['altdorf', 'Altdorf UR', 'Altdorf (UR)', 'UR'];
        yield ['bertschikon', 'Bertschikon (Gossau ZH)', 'Gossau (ZH)', 'ZH'];
        yield ['goldiwil', 'Goldiwil (Thun)', 'Thun', 'BE'];
    }
}
