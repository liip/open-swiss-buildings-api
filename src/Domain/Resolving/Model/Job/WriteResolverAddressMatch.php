<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\AdditionalData;
use Symfony\Component\Uid\Uuid;

final readonly class WriteResolverAddressMatch
{
    /**
     * @param int<0, 100>           $confidence
     * @param non-empty-string|null $matchingBuildingId
     * @param non-empty-string|null $matchingEntranceId
     */
    public function __construct(
        public Uuid $id,
        public int $confidence,
        public string $matchType,
        public ?string $matchingBuildingId,
        public ?string $matchingEntranceId,
        public AdditionalData $additionalData,
    ) {}
}
