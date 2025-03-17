<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Entity;

use App\Domain\BuildingData\Repository\BuildingEntranceRepository;
use App\Infrastructure\Address\Model\AddressFieldsTrait;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;
use App\Infrastructure\PostGis\Coordinates;
use App\Infrastructure\PostGis\CoordinatesParser;
use App\Infrastructure\PostGis\SRIDEnum;
use App\Infrastructure\PostGis\Types\TransformedWGS84GeometryType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Jsor\Doctrine\PostGIS\Types\PostGISType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: BuildingEntranceRepository::class)]
#[ORM\Index(fields: ['buildingId'], name: 'buildingId_idx')]
#[ORM\Index(fields: ['entranceId'], name: 'entranceId_idx')]
#[ORM\Index(fields: ['importedAt'], name: 'importedAt_idx')]
#[ORM\Index(fields: ['cantonCode'], name: 'cantonCode_idx')]
#[ORM\Index(fields: ['countryCode'], name: 'countryCode_idx')]
#[ORM\Index(fields: ['streetName', 'streetHouseNumber', 'streetHouseNumberSuffix', 'postalCode', 'locality'], name: 'building_entrance_idx')]
#[ORM\Index(fields: ['streetNameAbbreviated', 'streetHouseNumber', 'streetHouseNumberSuffix', 'postalCode', 'locality'], name: 'building_entrance_abbreviation_idx')]
#[ORM\Index(fields: ['streetNameNormalized', 'streetHouseNumber', 'streetHouseNumberSuffixNormalized', 'postalCode', 'localityNormalized'], name: 'building_entrance_normalized_idx')]
#[ORM\Index(fields: ['streetNameAbbreviatedNormalized', 'streetHouseNumber', 'streetHouseNumberSuffixNormalized', 'postalCode', 'localityNormalized'], name: 'building_entrance_abbreviation_normalized_idx')]
#[ORM\Index(fields: ['streetId', 'streetHouseNumber', 'streetHouseNumberSuffix'], name: 'building_entrance_street_id_idx')]
#[ORM\Index(fields: ['streetId', 'streetHouseNumber', 'streetHouseNumberSuffixNormalized'], name: 'building_entrance_street_id_normalized_idx')]
// The following is not supported by Doctrine. Added manually as a migration and set up IgnoredFieldsListener to have Migrations Diff not try to remove it.
// #[ORM\Index(fields: ['geoCoordinatesWgs84'], name: 'building_entrance_geo_coordinates_wgs84_idx_custom'), type: 'USING GIST']
#[ORM\UniqueConstraint(name: 'building_entrance_language', fields: ['countryCode', 'buildingId', 'entranceId', 'streetNameLanguage'])]
class BuildingEntrance
{
    use AddressFieldsTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    public UuidV7 $id;

    /**
     * Country code.
     */
    #[ORM\Column(length: 2)]
    public CountryCodeEnum $countryCode = CountryCodeEnum::CH;

    /**
     * Building identifier (numeric, 9chars).
     */
    #[ORM\Column(length: 9)]
    public string $buildingId = '';

    /**
     * Entrance identifier (numeric, 2chars).
     */
    #[ORM\Column(length: 2)]
    public string $entranceId = '';

    /**
     * Building-Address identifier (numeric, 9chars).
     */
    #[ORM\Column(length: 9)]
    public string $addressId = '';

    /**
     * Street ID.
     */
    #[ORM\Column(length: 8)]
    public string $streetId = '';

    /**
     * Street name, abbreviation (alpha-numeric, 24chars).
     */
    #[ORM\Column(length: 24)]
    public string $streetNameAbbreviated = '';

    /**
     * Abbreviated street name without special characters, used for matching.
     */
    #[ORM\Column(length: 32)]
    public string $streetNameAbbreviatedNormalized = '';

    /**
     * Street name language.
     */
    #[ORM\Column(length: 2)]
    public LanguageEnum $streetNameLanguage = LanguageEnum::UNKNOWN;

    /**
     * Municipality code (numeric, 4chars).
     */
    #[ORM\Column(length: 4)]
    public string $municipalityCode = '';

    /**
     * Municipality (alpha, 40chars).
     */
    #[ORM\Column(length: 40)]
    public string $municipality = '';

    /**
     * Canton code (alpha, 2chars).
     * Example: "ZH".
     */
    #[ORM\Column(length: 2)]
    public string $cantonCode = '';

    /**
     * We store the original CH1903+/LV95 coordinates as array too, to be able to retrieve
     * them without any PostGIS transformation.
     *
     * @var array{east: string, north: string}|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $coordinatesLV95 = null;

    /**
     * Geo-Coordinates in CH1903+/LV95 system
     * See: https://epsg.io/2056.
     */
    #[ORM\Column(
        type: PostGISType::GEOMETRY,
        nullable: true,
        options: ['geometry_type' => 'POINT', 'srid' => SRIDEnum::LV95->value],
    )]
    private ?string $geoCoordinatesLV95 = null;

    /**
     * Geo-Coordinates in WGS84 system
     * See: https://epsg.io/4326.
     */
    #[ORM\Column(
        type: TransformedWGS84GeometryType::NAME,
        nullable: true,
        options: ['geometry_type' => 'POINT', 'srid' => SRIDEnum::WGS84->value],
    )]
    private ?string $geoCoordinatesWGS84 = null;

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: false,
        options: ['default' => 'CURRENT_TIMESTAMP'],
    )]
    public \DateTimeImmutable $importedAt;

    public function __construct()
    {
        $this->id = new UuidV7();
        $this->markImportedNow();
    }

    public function getGeoCoordinatesWGS84Parsed(): ?Coordinates
    {
        return CoordinatesParser::parseWGS84($this->geoCoordinatesWGS84);
    }

    public function markImportedNow(): void
    {
        $this->importedAt = new \DateTimeImmutable();
    }
}
