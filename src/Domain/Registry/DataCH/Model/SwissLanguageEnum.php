<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Model;

use App\Infrastructure\Model\LanguageEnum;

/**
 * Enumeration of the STRSP address information.
 *
 * See SQLite table "codes" for more details and translations.
 */
enum SwissLanguageEnum: string
{
    case DE = '9901';
    case RM = '9902';
    case FR = '9903';
    case IT = '9904';
    case UNKNOWN = '';

    public static function fromLanguage(LanguageEnum $languageEnum): self
    {
        return match ($languageEnum) {
            LanguageEnum::DE => self::DE,
            LanguageEnum::FR => self::FR,
            LanguageEnum::IT => self::IT,
            LanguageEnum::RM => self::RM,
            LanguageEnum::UNKNOWN => self::UNKNOWN,
        };
    }

    public function toLanguage(): LanguageEnum
    {
        return match ($this) {
            self::DE => LanguageEnum::DE,
            self::FR => LanguageEnum::FR,
            self::IT => LanguageEnum::IT,
            self::RM => LanguageEnum::RM,
            self::UNKNOWN => LanguageEnum::UNKNOWN,
        };
    }
}
