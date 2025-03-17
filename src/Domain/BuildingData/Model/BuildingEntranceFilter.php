<?php

declare(strict_types=1);

namespace App\Domain\BuildingData\Model;

use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;

final readonly class BuildingEntranceFilter
{
    public function __construct(
        /**
         * @var list<CountryCodeEnum>|null
         */
        public ?array $countryCodes = null,

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
         * @var list<LanguageEnum>|null
         */
        public ?array $languages = null,
    ) {}
}
