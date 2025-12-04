<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\AdditionalData;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type AdditionalDataAsArray from AdditionalData
 */
final readonly class ResolverTask
{
    public Uuid $id;

    public Uuid $jobId;

    /**
     * @var int<0, 100>
     */
    public int $confidence;

    public string $matchType;

    /**
     * @var non-empty-string|null
     */
    public ?string $matchingBuildingId;

    /**
     * @var non-empty-string|null
     */
    public ?string $matchingMunicipalityCode;

    /**
     * @var non-empty-string|null
     */
    public ?string $matchingEntranceId;

    public AdditionalData $additionalData;

    /**
     * @param int<0, 100>                                $confidence
     * @param non-empty-string|null                      $matchingBuildingId
     * @param non-empty-string|null                      $matchingMunicipalityCode
     * @param non-empty-string|null                      $matchingEntranceId
     * @param AdditionalData|list<AdditionalDataAsArray> $additionalData           array structure is supported for creating based on source data
     */
    public function __construct(
        string $id,
        string $jobId,
        int $confidence,
        string $matchType,
        ?string $matchingBuildingId,
        ?string $matchingMunicipalityCode,
        ?string $matchingEntranceId,
        AdditionalData|array $additionalData,
    ) {
        $this->id = Uuid::fromString($id);
        $this->jobId = Uuid::fromString($jobId);
        $this->confidence = $confidence;
        $this->matchType = $matchType;
        $this->matchingBuildingId = $matchingBuildingId;
        $this->matchingMunicipalityCode = $matchingMunicipalityCode;
        $this->matchingEntranceId = $matchingEntranceId;

        if (\is_array($additionalData)) {
            $additionalData = AdditionalData::createFromList($additionalData);
        }
        $this->additionalData = $additionalData;
    }

    public function getConfidenceAsFloat(): float
    {
        return $this->confidence / 100;
    }
}
