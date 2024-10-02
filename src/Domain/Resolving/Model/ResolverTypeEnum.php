<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model;

enum ResolverTypeEnum: string
{
    case BUILDING_IDS = 'building_ids';
    case MUNICIPALITIES_CODES = 'municipalities_codes';
    case GEO_JSON = 'geo_json';
    case ADDRESS_SEARCH = 'address_search';
}
