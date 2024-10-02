<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

final readonly class BuildingEntranceFilter
{
    public function __construct(
        /**
         * @var list<non-empty-string>|null
         */
        public ?array $cantonCodes = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $buildingIds = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $entranceIds = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $municipalities = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $streetNames = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $streetIds = null,

        /**
         * @var list<EntranceLanguageEnum>|null
         */
        public ?array $languages = null,
    ) {}
}
