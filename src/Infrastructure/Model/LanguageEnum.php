<?php

declare(strict_types=1);

namespace App\Infrastructure\Model;

enum LanguageEnum: string
{
    case DE = 'de';

    case FR = 'fr';

    case RM = 'rm';

    case IT = 'it';

    case UNKNOWN = '';
}
