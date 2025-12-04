<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

enum RangeAddressTypeEnum: string
{
    case HOUSE_NUMBER = 'number';

    case HOUSE_NUMBER_SUFFIX = 'suffix';
}
