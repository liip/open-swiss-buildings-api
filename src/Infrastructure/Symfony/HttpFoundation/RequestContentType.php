<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\HttpFoundation;

final readonly class RequestContentType
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $type,
        /**
         * @var non-empty-string|null
         */
        public ?string $charset,
    ) {}
}
