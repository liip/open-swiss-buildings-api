<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Result;

use App\Domain\Resolving\Handler\AddressSearch\DoctrineClosestHouseNumberMatcher;
use App\Domain\Resolving\Handler\AddressSearch\DoctrineExactAddressMatcher;
use App\Domain\Resolving\Handler\AddressSearch\DoctrineNothingMatcher;
use App\Domain\Resolving\Handler\AddressSearch\DoctrineStreetIdMatcher;
use App\Domain\Resolving\Handler\AddressSearch\DoctrineStreetMatcher;
use App\Domain\Resolving\Handler\AddressSearch\DoctrineStreetWithRangeMatcher;
use App\Domain\Resolving\Handler\AddressSearch\SearchMatcher;
use App\Domain\Resolving\Handler\AddressSearch\SearchStreetMatcher;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Confidence;
use App\Infrastructure\PostGis\Coordinates;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * @phpstan-import-type AdditionalDataAsArray from AdditionalData
 */
final readonly class ResolverResult implements \JsonSerializable
{
    /**
     * @var non-empty-string|null
     */
    #[OA\Property(property: 'id', format: 'uuid')]
    public ?string $buildingEntranceId;

    /**
     * @var non-empty-string|null
     */
    #[OA\Property(property: 'country_code')]
    public ?string $countryCode;

    /**
     * @var non-empty-string|null
     */
    #[OA\Property(property: 'building_id')]
    public ?string $buildingId;

    /**
     * @var non-empty-string|null
     */
    #[OA\Property(property: 'entrance_id')]
    public ?string $entranceId;

    public ?Address $address;
    public ?Coordinates $coordinates;

    #[Serializer\Ignore]
    public Confidence $confidence;

    public string $matchType;

    #[OA\Property(property: 'additional_data', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]
    public AdditionalData $additionalData;

    /**
     * @param non-empty-string|null $buildingEntranceId
     * @param non-empty-string|null $countryCode
     * @param non-empty-string|null $buildingId
     * @param non-empty-string|null $entranceId
     * @param AdditionalData        $additionalData     a list of additional data/columns of the result
     */
    public function __construct(
        Confidence $confidence,
        string $matchType,
        ?string $buildingEntranceId,
        ?string $countryCode,
        ?string $buildingId,
        ?string $entranceId,
        ?Address $address,
        ?Coordinates $coordinates,
        AdditionalData $additionalData,
    ) {
        $this->confidence = $confidence;
        $this->matchType = $matchType;
        $this->buildingEntranceId = $buildingEntranceId;
        $this->countryCode = $countryCode;
        $this->buildingId = $buildingId;
        $this->entranceId = $entranceId;
        $this->coordinates = $coordinates;
        $this->address = $address;
        $this->additionalData = $additionalData;
    }

    #[OA\Property(property: 'confidence')]
    public function getConfidenceAsFloat(): float
    {
        return $this->confidence->asFloat();
    }

    public function getMatchTypeInfo(): string
    {
        $matchInfo = [];
        $parts = explode('-', $this->matchType);
        $counter = \count($parts);
        for ($i = 0; $i < $counter; ++$i) {
            $part = $parts[$i];
            $matchInfo[] = match ($part) {
                'buildingId' => 'Matched on building ID (EGID/GEID)',
                'municipalityCode' => 'Matched on municipality code',
                'geoJson' => 'Matched on GeoJSON',
                DoctrineStreetMatcher::TYPE_STREET_EXACT => 'Street matched exactly',
                DoctrineStreetMatcher::TYPE_STREET_EXACT_NORMALIZED => 'Street matched exactly (normalized)',
                SearchStreetMatcher::TYPE_STREET_SEARCH => 'Street matched by search',
                DoctrineExactAddressMatcher::TYPE_EXACT => 'Address matched exactly',
                DoctrineExactAddressMatcher::TYPE_EXACT_NORMALIZED => 'Address matched exactly (normalized)',
                DoctrineStreetIdMatcher::TYPE_STREET_FULL => 'Address matched exactly on street',
                DoctrineStreetIdMatcher::TYPE_STREET_HOUSE_NUMBER_WITHOUT_SUFFIX => 'Address matched without suffix',
                DoctrineStreetIdMatcher::TYPE_STREET_HOUSE_NUMBERS_WITH_SUFFIX => 'Address matched with suffix',
                DoctrineStreetIdMatcher::TYPE_STREET_HOUSE_NUMBERS_WITH_OTHER_SUFFIX => 'Address matched with other suffix',
                DoctrineClosestHouseNumberMatcher::TYPE_STREET_CLOSEST_HOUSE_NUMBER => 'Address matched with closest house number',
                DoctrineStreetWithRangeMatcher::TYPE_STREET_EXACT_W_RANGE => 'Address matched exactly within house-number range',
                DoctrineStreetWithRangeMatcher::TYPE_STREET_EXACT_NORMALIZED_W_RANGE => 'Address matched exactly (normalized) within house-number range',
                DoctrineStreetWithRangeMatcher::TYPE_STREET_EXACT_W_RANGE_SUFFIX => 'Address matched exactly within house-number suffix range',
                DoctrineStreetWithRangeMatcher::TYPE_STREET_EXACT_NORMALIZED_W_SUFFIX_RANGE => 'Address matched exactly (normalized) within house-number suffix range',
                SearchMatcher::TYPE_SEARCH => 'Address matched by search',
                DoctrineNothingMatcher::TYPE_NOTHING => 'Address did not match',
                default => 'Match unknown',
            };
        }

        return implode(', ', $matchInfo);
    }

    /**
     * @return array{
     *     id: string|null,
     *     confidence: float,
     *     match_type: string,
     *     building_id?: string,
     *     entrance_id?: string,
     *     address?: Address,
     *     additional_data?: AdditionalDataAsArray
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->buildingEntranceId,
            'confidence' => $this->getConfidenceAsFloat(),
            'match_type' => $this->matchType,
        ];

        if (null !== $this->buildingId) {
            $data['building_id'] = $this->buildingId;
            $data['country_code'] = $this->countryCode;

            if (null !== $this->entranceId && null !== $this->address) {
                $data['entrance_id'] = $this->entranceId;
                $data['address'] = $this->address;
            }
        }

        if (!$this->additionalData->isEmpty()) {
            $data['additional_data'] = $this->additionalData->getDataWithInternal();
        }

        return $data;
    }
}
