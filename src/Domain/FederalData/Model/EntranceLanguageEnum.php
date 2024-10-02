<?php

declare(strict_types=1);

namespace App\Domain\FederalData\Model;

/**
 * Enumeration of the STRSP address information.
 *
 * See SQLite table "codes" for more details and translations.
 */
enum EntranceLanguageEnum: string
{
    case DE = '9901';
    case RM = '9902';
    case FR = '9903';
    case IT = '9904';
    case UNKNOWN = '';
}
