<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

final readonly class Encoder
{
    public const string DATE_FORMAT = \DateTime::ISO8601;

    private function __construct() {}
}
