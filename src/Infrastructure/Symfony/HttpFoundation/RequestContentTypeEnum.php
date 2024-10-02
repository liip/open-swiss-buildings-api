<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\HttpFoundation;

enum RequestContentTypeEnum: string
{
    case JSON = 'application/json';
    case CSV = 'text/csv';
    case WILDCARD = '*/*';
}
