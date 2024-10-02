<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\AdditionalData;
use App\Infrastructure\Address\Model\Street;
use Symfony\Component\Uid\Uuid;

final readonly class ResolverAddress
{
    /**
     * @param non-empty-string|null $postalCode
     * @param non-empty-string|null $locality
     */
    public function __construct(
        public Uuid $id,
        public Uuid $jobId,
        public ?Street $street,
        public ?string $postalCode,
        public ?string $locality,
        public AdditionalData $additionalData,
    ) {}
}
