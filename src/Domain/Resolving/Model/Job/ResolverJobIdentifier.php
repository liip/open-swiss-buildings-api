<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class ResolverJobIdentifier implements \Stringable
{
    public function __construct(
        public Uuid $id,
        public ResolverTypeEnum $type,
    ) {}

    public function __toString(): string
    {
        return "{$this->id} ({$this->type->value})";
    }
}
