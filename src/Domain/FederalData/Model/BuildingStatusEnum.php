<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Model;

/**
 * Enumeration of the GSTAT building information.
 *
 * See SQLite table "codes" for more details and translations.
 */
enum BuildingStatusEnum: string
{
    case PLANNED = '1001';
    case AUTHORIZED = '1002';
    case IN_CONSTRUCTION = '1003';
    case EXISTING = '1004';
    case NOT_USABLE = '1005';
    case UNKNOWN_06 = '1006';
    case DEMOLISHED = '1007';
    case NOT_BUILT = '1008';

    case UNKNOWN = '';
}
