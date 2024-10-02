<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

use App\Infrastructure\Address\Model\Street;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\PostGis\Coordinates;
use App\Infrastructure\PostGis\CoordinatesParser;
use App\Infrastructure\Serialization\Decoder;
use Symfony\Component\Uid\Uuid;

final readonly class BuildingEntrance
{
    public function __construct(
        public Uuid $id,
        public string $buildingId,
        public string $entranceId,
        public string $addressId,
        public string $streetId,
        public ?Street $street,
        public ?Street $streetAbbreviated,
        public EntranceLanguageEnum $streetNameLanguage,
        public string $postalCode,
        public string $locality,
        public string $municipalityCode,
        public string $municipality,
        public string $cantonCode,
        public ?Coordinates $coordinates,
        public \DateTimeImmutable $importedAt,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromScalarArray(array $data): self
    {
        $number = StreetNumber::createOptional(
            Decoder::readOptionalPositiveInt($data, 'street_house_number', true),
            Decoder::readOptionalNonEmptyString($data, 'street_house_number_suffix', true),
        );

        return new self(
            Uuid::fromString(Decoder::readNonEmptyString($data, 'id')),
            Decoder::readString($data, 'building_id'),
            Decoder::readString($data, 'entrance_id'),
            Decoder::readString($data, 'address_id'),
            Decoder::readString($data, 'street_id'),
            Street::createOptional(Decoder::readOptionalNonEmptyString($data, 'street_name', true), $number),
            Street::createOptional(Decoder::readOptionalNonEmptyString($data, 'street_name_abbreviated', true), $number),
            Decoder::readBackedEnum($data, 'street_name_language', EntranceLanguageEnum::class),
            Decoder::readString($data, 'postal_code'),
            Decoder::readString($data, 'locality'),
            Decoder::readString($data, 'municipality_code'),
            Decoder::readString($data, 'municipality'),
            Decoder::readString($data, 'canton_code'),
            CoordinatesParser::parseWGS84(Decoder::readOptionalString($data, 'geo_coordinates_wgs84')),
            Decoder::readDateTime($data, 'imported_at', 'Y-m-d H:i:s'),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $number = StreetNumber::createOptional(
            Decoder::readOptionalPositiveInt($data, 'streetHouseNumber', true),
            Decoder::readOptionalNonEmptyString($data, 'streetHouseNumberSuffix', true),
        );

        return new self(
            Decoder::readUuid($data, 'id'),
            Decoder::readString($data, 'buildingId'),
            Decoder::readString($data, 'entranceId'),
            Decoder::readString($data, 'addressId'),
            Decoder::readString($data, 'streetId'),
            Street::createOptional(Decoder::readOptionalNonEmptyString($data, 'streetName', true), $number),
            Street::createOptional(Decoder::readOptionalNonEmptyString($data, 'streetNameAbbreviated', true), $number),
            Decoder::readBackedEnum($data, 'streetNameLanguage', EntranceLanguageEnum::class),
            Decoder::readString($data, 'postalCode'),
            Decoder::readString($data, 'locality'),
            Decoder::readString($data, 'municipalityCode'),
            Decoder::readString($data, 'municipality'),
            Decoder::readString($data, 'cantonCode'),
            CoordinatesParser::parseWGS84(Decoder::readOptionalString($data, 'geoCoordinatesWGS84')),
            Decoder::readDateTime($data, 'importedAt', 'Y-m-d H:i:s'),
        );
    }

    public function getAddress(): string
    {
        $street = '';
        if (null !== $this->street) {
            $street = "{$this->street}, ";
        } elseif (null !== $this->streetAbbreviated) {
            $street = "{$this->streetAbbreviated}, ";
        }

        return "{$street}{$this->postalCode} {$this->locality}";
    }
}
