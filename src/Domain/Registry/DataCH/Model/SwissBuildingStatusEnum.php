<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Model;

use App\Domain\Registry\Model\BuildingStatusEnum;

/**
 * Enumeration of the GSTAT building information.
 *
 * See SQLite table "codes" for more details and translations.
 */
enum SwissBuildingStatusEnum: string
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

    public function toBuildingStatus(): BuildingStatusEnum
    {
        return match ($this) {
            self::PLANNED => BuildingStatusEnum::PLANNED,
            self::AUTHORIZED => BuildingStatusEnum::AUTHORIZED,
            self::IN_CONSTRUCTION => BuildingStatusEnum::IN_CONSTRUCTION,
            self::EXISTING => BuildingStatusEnum::EXISTING,
            self::NOT_USABLE => BuildingStatusEnum::NOT_USABLE,
            self::DEMOLISHED => BuildingStatusEnum::DEMOLISHED,
            self::NOT_BUILT => BuildingStatusEnum::NOT_BUILT,
            self::UNKNOWN, self::UNKNOWN_06 => BuildingStatusEnum::UNKNOWN,
        };
    }
}
