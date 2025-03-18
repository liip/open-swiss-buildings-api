<?php

declare(strict_types=1);

namespace App\Application\Messaging\Message;

final readonly class AddressSearchIndexUpdatedAfterMessage implements AsyncDefaultMessage
{
    public function __construct(
        public \DateTimeImmutable $timestamp,
    ) {}
}
