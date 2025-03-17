<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

final readonly class SearchStats
{
    public function __construct(
        public string $status,
        public int $indexedAddresses,
    ) {}
}
