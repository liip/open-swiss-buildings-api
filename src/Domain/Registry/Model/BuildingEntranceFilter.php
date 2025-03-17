<?php

declare(strict_types=1);

namespace App\Domain\Registry\Model;

use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;

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
        public ?array $municipalityNames = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $streetNames = null,

        /**
         * @var list<non-empty-string>|null
         */
        public ?array $streetIds = null,

        /**
         * @var list<LanguageEnum>|null
         */
        public ?array $languages = null,

        /**
         * @var list<BuildingStatusEnum>|null
         */
        public ?array $buildingStatuses = null,

        /**
         * @var list<CountryCodeEnum>|null
         */
        public ?array $countryCode = null,
    ) {}
}
