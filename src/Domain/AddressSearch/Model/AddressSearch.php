<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Model;

final readonly class AddressSearch
{
    public const int DEFAULT_LIMIT = 40;

    public function __construct(
        /**
         * @var positive-int|null
         */
        public ?int $limit = null,

        /**
         * Min score of results.
         *
         * @var int<1, 100>|null
         */
        public ?int $minScore = null,

        /**
         * @var non-empty-string|null
         */
        public ?string $filterByQuery = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $filterByIds = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $filterByBuildingIds = null,
    ) {}
}
