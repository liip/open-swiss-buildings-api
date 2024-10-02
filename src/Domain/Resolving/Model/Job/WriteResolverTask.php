<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WriteResolverTask
{
    public Uuid $id;

    /**
     * @param int<0, 100> $confidence
     */
    public function __construct(
        public Uuid $jobId,
        public ResolverTypeEnum $jobType,
        public AdditionalData $additionalData,
        public int $confidence,
        public string $matchType,
        public ?string $matchingBuildingId = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingMunicipalityCode = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingEntranceId = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingGeoJson = null,
    ) {
        $this->id = Uuid::v7();
    }

    /**
     * @param int<0, 100> $confidence
     */
    public static function forBuildingIdJob(Uuid $jobId, int $confidence, string $matchingBuildingId, AdditionalData $additionalData): self
    {
        return new self(
            jobId: $jobId,
            jobType: ResolverTypeEnum::BUILDING_IDS,
            additionalData: $additionalData,
            confidence: $confidence,
            matchType: 'buildingId',
            matchingBuildingId: $matchingBuildingId,
        );
    }

    /**
     * @param int<0, 100>      $confidence
     * @param non-empty-string $matchingMunicipalityCode
     */
    public static function forMunicipalityCode(Uuid $jobId, int $confidence, string $matchingMunicipalityCode, AdditionalData $additionalData): self
    {
        return new self(
            jobId: $jobId,
            jobType: ResolverTypeEnum::MUNICIPALITIES_CODES,
            additionalData: $additionalData,
            confidence: $confidence,
            matchType: 'municipalityCode',
            matchingMunicipalityCode: $matchingMunicipalityCode,
        );
    }

    /**
     * @param int<0, 100>      $confidence
     * @param non-empty-string $matchingGeoJson
     */
    public static function forGeoJsonJob(Uuid $jobId, int $confidence, string $matchingGeoJson, AdditionalData $additionalData): self
    {
        return new self(
            jobId: $jobId,
            jobType: ResolverTypeEnum::GEO_JSON,
            additionalData: $additionalData,
            confidence: $confidence,
            matchType: 'geoJson',
            matchingGeoJson: $matchingGeoJson,
        );
    }
}
