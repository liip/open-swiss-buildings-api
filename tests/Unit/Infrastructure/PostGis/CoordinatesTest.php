<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\PostGis;

use App\Infrastructure\PostGis\Coordinates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Coordinates::class)]
final class CoordinatesTest extends TestCase
{
    public function testCreationWithConstructorNamedArguments(): void
    {
        $c = new Coordinates(latitude: '8.52929', longitude: '47.38623');
        $this->assertSameCoords($c, '8.52929', '47.38623');
    }

    public function testCreationWithConstructor(): void
    {
        $c = new Coordinates('8.52929', '47.38623');
        $this->assertSameCoords($c, '8.52929', '47.38623');
    }

    public function testCreationWithArray(): void
    {
        $c = Coordinates::fromArray(['latitude' => '8.52929', 'longitude' => '47.38623']);
        $this->assertSameCoords($c, '8.52929', '47.38623');
    }

    private function assertSameCoords(Coordinates $c, string $lat, string $lon): void
    {
        $this->assertSame($lat, $c->latitude);
        $this->assertSame($lon, $c->longitude);

        $this->assertSame(['latitude' => $lat, 'longitude' => $lon], $c->jsonSerialize());
        $this->assertSame("{$lat}/{$lon}", (string) $c);
    }
}
