<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Infrastructure\Pagination;
use App\Infrastructure\SchemaOrg\Place;
use Symfony\Component\Uid\UuidV7;

interface BuildingAddressFinderInterface
{
    /**
     * Tries to find the address with the given ID.
     */
    public function findPlace(UuidV7 $id): ?Place;

    /**
     * Tries to find the addresses matching the given filter.
     *
     * @return iterable<Place>
     */
    public function findPlaces(Pagination $pagination): iterable;
}
