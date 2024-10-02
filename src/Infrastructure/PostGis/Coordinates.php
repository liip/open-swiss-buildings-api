<?php

declare(strict_types=1);

namespace App\Infrastructure\PostGis;

use App\Infrastructure\Serialization\Decoder;

/**
 * @phpstan-type GeoCoordinatesAsArray array{
 *    latitude: numeric-string,
 *    longitude: numeric-string,
 *  }
 */
final readonly class Coordinates implements \Stringable, \JsonSerializable
{
    public function __construct(
        /**
         * @var numeric-string
         */
        public string $latitude,
        /**
         * @var numeric-string
         */
        public string $longitude,
    ) {}

    /**
     * @param array<string|int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            latitude: Decoder::readNumericString($data, 'latitude'),
            longitude: Decoder::readNumericString($data, 'longitude'),
        );
    }

    public function __toString(): string
    {
        return "{$this->latitude}/{$this->longitude}";
    }

    /**
     * @return GeoCoordinatesAsArray
     */
    public function jsonSerialize(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
