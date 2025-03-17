<?php

declare(strict_types=1);

namespace App\Infrastructure\SchemaOrg;

final readonly class PlaceLinearizer
{
    private function __construct() {}

    /**
     * @return string[]
     */
    public static function headers(): array
    {
        return [
            'identifier',
            'postalAddress.addressCountry',
            'postalAddress.addressLocality',
            'postalAddress.addressRegion',
            'postalAddress.postalCode',
            'postalAddress.streetAddress',
            'postalAddress.inLanguage',
            'geo.latitude',
            'geo.longitude',
            'additionalProperty.buildingId',
            'additionalProperty.entranceId',
            'additionalProperty.addressId',
            'additionalProperty.municipalityCode',
        ];
    }

    /**
     * @return array{
     * "identifier": string,
     * "postalAddress.addressCountry": string,
     * "postalAddress.addressLocality": string,
     * "postalAddress.addressRegion": string,
     * "postalAddress.postalCode": string,
     * "postalAddress.streetAddress": string,
     * "postalAddress.inLanguage": string,
     * "geo.latitude": ?string,
     * "geo.longitude": ?string,
     * "additionalProperty.buildingId": string,
     * "additionalProperty.entranceId": string,
     * "additionalProperty.addressId": string,
     * "additionalProperty.municipalityCode": string,
     * }
     */
    public static function linearized(Place $place): array
    {
        return [
            'identifier' => $place->identifier,
            'postalAddress.addressCountry' => $place->postalAddress->addressCountry,
            'postalAddress.addressLocality' => $place->postalAddress->addressLocality,
            'postalAddress.addressRegion' => $place->postalAddress->addressRegion,
            'postalAddress.postalCode' => $place->postalAddress->postalCode,
            'postalAddress.streetAddress' => $place->postalAddress->streetAddress,
            'postalAddress.inLanguage' => $place->postalAddress->inLanguage,
            'geo.latitude' => $place->geo?->latitude,
            'geo.longitude' => $place->geo?->longitude,
            'additionalProperty.buildingId' => $place->additionalProperty->buildingId,
            'additionalProperty.entranceId' => $place->additionalProperty->entranceId,
            'additionalProperty.addressId' => $place->additionalProperty->addressId,
            'additionalProperty.municipalityCode' => $place->additionalProperty->municipalityCode];
    }
}
