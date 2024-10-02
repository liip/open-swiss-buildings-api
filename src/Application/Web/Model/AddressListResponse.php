<?php

declare(strict_types=1);

namespace App\Application\Web\Model;

use App\Infrastructure\SchemaOrg\Place;

final readonly class AddressListResponse
{
    public function __construct(
        public int $total,

        /**
         * @var iterable<Place>
         */
        public iterable $results,
    ) {}

    /**
     * @return array{total: int, results: iterable<Place>}
     */
    public function toIterable(): iterable
    {
        return [
            'total' => $this->total,
            'results' => $this->results,
        ];
    }
}
