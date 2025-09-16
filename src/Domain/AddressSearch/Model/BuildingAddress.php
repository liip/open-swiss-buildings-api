<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

use App\Infrastructure\Model\LanguageEnum;
use App\Infrastructure\PostGis\Coordinates;
use App\Infrastructure\Serialization\Decoder;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type AddressAsArray from Address
 * @phpstan-import-type GeoCoordinatesAsArray from Coordinates
 *
 * @phpstan-type BuildingAddressAsArray array{
 *    id: non-empty-string,
 *    buildingId: non-empty-string,
 *    addressId: non-empty-string,
 *    entranceId: non-empty-string,
 *    streetId: string|null,
 *    language: value-of<LanguageEnum>,
 *    address: AddressAsArray,
 *    coordinates: GeoCoordinatesAsArray|null,
 *    importedAt: int,
 *  }
 */
final readonly class BuildingAddress implements \JsonSerializable
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $id,
        /**
         * @var non-empty-string
         */
        public string $buildingId,
        /**
         * @var non-empty-string
         */
        public string $addressId,
        /**
         * @var non-empty-string
         */
        public string $entranceId,
        /**
         * @var non-empty-string|null
         */
        public ?string $streetId,
        /**
         * @var value-of<LanguageEnum>
         */
        public string $language,
        public Address $address,
        public ?Coordinates $coordinates,
        /**
         * Date representation in the format YYYYMMDD.
         */
        public int $importedAt,
    ) {}

    /**
     * @return non-empty-string
     */
    public static function extractIdentifier(Uuid $uuidV7): string
    {
        return $uuidV7->toRfc4122();
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Decoder::readNonEmptyString($data, 'id'),
            buildingId: Decoder::readNonEmptyString($data, 'buildingId'),
            addressId: Decoder::readNonEmptyString($data, 'addressId'),
            entranceId: Decoder::readNonEmptyString($data, 'entranceId'),
            streetId: Decoder::readOptionalNonEmptyString($data, 'streetId'),
            language: Decoder::readBackedEnum($data, 'language', LanguageEnum::class)->value,
            address: Decoder::readObject($data, 'address', Address::class),
            coordinates: Decoder::readOptionalObject($data, 'coordinates', Coordinates::class),
            importedAt: Decoder::readInt($data, 'importedAt'),
        );
    }

    /**
     * @return BuildingAddressAsArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'buildingId' => $this->buildingId,
            'addressId' => $this->addressId,
            'entranceId' => $this->entranceId,
            'streetId' => $this->streetId,
            'language' => $this->language,
            'importedAt' => $this->importedAt,
            'address' => $this->address->jsonSerialize(),
            'coordinates' => $this->coordinates?->jsonSerialize(),
        ];
    }
}
