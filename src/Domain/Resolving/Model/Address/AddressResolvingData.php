<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Address;

use App\Domain\Resolving\Model\AdditionalData;
use App\Infrastructure\Address\Model\Street;
use Symfony\Component\Uid\Uuid;

/**
 * This is the data for resolving an address.
 */
final readonly class AddressResolvingData
{
    public function __construct(
        public Uuid $referenceId,
        public Street $street,
        /**
         * @var non-empty-string
         */
        public string $postalCode,
        /**
         * @var non-empty-string
         */
        public string $locality,
        public AdditionalData $additionalData,
    ) {}

    /**
     * @return non-empty-string
     */
    public function getAddress(): string
    {
        return "{$this->street}, {$this->postalCode} {$this->locality}";
    }

    /**
     * @return non-empty-string
     */
    public function getStreet(): string
    {
        return "{$this->street->streetName}, {$this->postalCode} {$this->locality}";
    }
}
