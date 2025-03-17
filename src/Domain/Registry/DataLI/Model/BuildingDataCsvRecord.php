<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI\Model;

use App\Domain\Registry\Model\BuildingEntranceData;
use App\Domain\Registry\Model\BuildingStatusEnum;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;
use League\Csv\Serializer\MapCell;

final class BuildingDataCsvRecord
{
    /**
     * Identifikationsnummer des Gebäudes im GWR. Der GEID ist eine landesweit eindeutige Identifikati‐
     * onsnummer für alle im GWR erfassten Gebäude.
     */
    #[MapCell(column: 'GEID')]
    public string $geid;

    /**
     * Identifikationsnummer der Gebäudeadresse im GWR. Bei mehreren Adressen wird die Hauptadresse
     * mit GEDID 1 erfasst. Weitere Adressen des gleichen Gebäudes erhalten die Nummern 2, 3, usw.
     *
     * Note: This is similar to the EDID property in the Swiss registry, but only for entrances that are
     * assigned different addresses.
     */
    #[MapCell(column: 'GEDID')]
    public string $gedid;

    /**
     * Land: Liechtenstein.
     */
    #[MapCell(column: 'COUNTRY')]
    public string $country;

    /**
     * Gemeindenummer: Statistische Nummerierung der Gemeinde (Hoheitsgebiet), in der das Gebäude liegt.
     */
    #[MapCell(column: 'GDENR')]
    public string $gdenr;

    /**
     * Gemeindename: Name der Gemeinde (Hoheitsgebiet), in der das Gebäude liegt.
     */
    #[MapCell(column: 'GDENAME')]
    public string $gdename;

    /**
     * Strassenname in Langform (ohne Abkürzungen) als Teil der Gebäudeadresse.
     */
    #[MapCell(column: 'STRNAME')]
    public string $strname;

    /**
     * Hausnummer: Hausnummer als Teil der Gebäudeadresse.
     */
    #[MapCell(column: 'DEINR')]
    public ?string $deinr;

    /**
     * 4‐stellige Postleitzahl der postalischen Adesse des Gebäudes gemäss amtlicher Vermessung.
     */
    #[MapCell(column: 'PLZ4')]
    public string $plz4;

    /**
     * Ortsname der postalischen Adresse des Gebäudes gemäss amtlicher Vermessung.
     */
    #[MapCell(column: 'PLZNAME')]
    public string $plzname;

    /**
     * Geografische Koordinaten Ost: Geografischer Referenzpunkt zur Lokalisierung des Gebäudes im
     * Koordinatennetz (LV95) der amtlichen Vermessung.
     */
    #[MapCell(column: 'EKODE')]
    public string $ekode;

    /**
     * Geografische Koordinaten Nord: Geografischer Referenzpunkt zur Lokalisierung des Gebäudes im
     * Koordinatennetz (LV95) der amtlichen Vermessung.
     */
    #[MapCell(column: 'EKODN')]
    public string $ekodn;

    /**
     * Sprache des Strassennamens: In Liechtenstein werden Strassen ausschliesslich in deutscher Sprache verwendet.
     */
    #[MapCell(column: 'STRSP')]
    public string $strsp;

    /**
     * Gebäudekategorie: Beschreibung des Zwecks oder die Art des Gebäudes.
     */
    #[MapCell(column: 'GEBKAT')]
    public string $gebkat;

    /**
     * Parzellennummer: Identifikationsnummer der Liegenschaft gemäss amtlicher Vermessung.
     */
    #[MapCell(column: 'GPARZ')]
    public string $gparz;

    /**
     * Bezugsdatum: Datum des letzten Datenbezugs.
     */
    #[MapCell(column: 'EXTDAT')]
    public string $extdat;

    public function asBuildingEntranceData(): BuildingEntranceData
    {
        return new BuildingEntranceData(
            buildingId: $this->geid,
            entranceId: $this->gedid,
            addressId: $this->geid . '.' . $this->gedid,
            streetHouseNumber: $this->normalizeNullValue($this->deinr),
            streetId: '',
            streetName: $this->convertEncoding($this->strname),
            streetNameAbbreviation: '',
            streetNameLanguage: LanguageEnum::DE,
            countryCode: CountryCodeEnum::LI,
            postalCode: $this->plz4,
            locality: $this->convertEncoding($this->plzname),
            municipality: $this->convertEncoding($this->gdename),
            municipalityCode: $this->gdenr,
            cantonCode: 'LI',
            geoCoordinateEastLV95: $this->ekode,
            geoCoordinateNorthLV95: $this->ekodn,
            // The "Public" dataset of liechtenstein does not include the status. We hope the CSV does not include demolished buildings.
            buildingStatus: BuildingStatusEnum::EXISTING,
        );
    }

    private function convertEncoding(string $value): string
    {
        return mb_convert_encoding($value, 'utf8', 'iso-8859-15');
    }

    private function normalizeNullValue(?string $value = null): string
    {
        if (null === $value || 'NULL' === $value) {
            return '';
        }

        return $value;
    }

    public function computeHash(): string
    {
        return hash('xxh3', implode('.', [
            $this->country,
            $this->geid,
            $this->gedid,
        ]));
    }
}
