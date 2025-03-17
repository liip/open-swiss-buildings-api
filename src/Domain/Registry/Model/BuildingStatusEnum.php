<?php

declare(strict_types=1);

namespace App\Domain\Registry\Model;

enum BuildingStatusEnum: string
{
    case PLANNED = 'planned';
    case AUTHORIZED = 'authorized';
    case IN_CONSTRUCTION = 'in construction';
    case EXISTING = 'existing';
    case NOT_USABLE = 'not usable';
    case DEMOLISHED = 'demolished';
    case NOT_BUILT = 'not built';

    case UNKNOWN = '';
}
