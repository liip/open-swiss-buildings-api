<?php

declare(strict_types=1);

namespace App\Infrastructure;

final readonly class Pagination
{
    public function __construct(
        /**
         * @var positive-int
         */
        public int $limit,
        /**
         * @var non-negative-int
         */
        public int $offset = 0,
    ) {}

    public function next(): self
    {
        return new self($this->limit, $this->offset + $this->limit);
    }
}
