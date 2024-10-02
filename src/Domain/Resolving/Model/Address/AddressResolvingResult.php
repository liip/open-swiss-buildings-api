<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Address;

/**
 * This is a result of resolving/searching an address.
 */
final readonly class AddressResolvingResult
{
    private function __construct(
        public AddressResolvingData $address,
        /**
         * @var int<0, 100>|null
         */
        public ?int $confidence = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matching = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingBuildingId = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingEntranceId = null,
        /**
         * @var non-empty-string|null
         */
        public ?string $matchingStreetId = null,
    ) {}

    /**
     * @param int<0, 100>           $confidence
     * @param non-empty-string      $matchingBuildingId
     * @param non-empty-string      $matchingEntranceId
     * @param non-empty-string|null $matchingStreetId
     * @param non-empty-string|null $matching
     */
    public static function matched(
        AddressResolvingData $address,
        int $confidence,
        string $matchingBuildingId,
        string $matchingEntranceId,
        ?string $matchingStreetId,
        ?string $matching,
    ): self {
        return new self($address, $confidence, $matching, $matchingBuildingId, $matchingEntranceId, $matchingStreetId);
    }

    public static function notMatched(AddressResolvingData $address): self
    {
        return new self($address);
    }
}
