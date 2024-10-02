<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Parser;

enum StreetTokenEnum
{
    case T_DOT;
    case T_UNKNOWN;
    case T_NUMBER;
    case T_STREET_NAME;
    case T_HOUSE_NUMBER;
    case T_HOUSE_NUMBER_RANGE;
    case T_HOUSE_NUMBER_SUFFIX_RANGE;
}
