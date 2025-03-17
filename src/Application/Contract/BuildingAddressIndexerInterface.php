<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Uid\Uuid;

interface BuildingAddressIndexerInterface
{
    /**
     * Returns the amount of building addresses available to index, filtered byt the country code, if given.
     */
    public function count(?CountryCodeEnum $countryCode = null): int;

    /**
     * Indexes all building addresses for search, filtered byt the country code, if given.
     *
     * @return iterable<BuildingAddress>
     */
    public function indexAll(?CountryCodeEnum $countryCode = null): iterable;

    /**
     * Indexes building addresses for search, which were imported since the given timestamp.
     *
     * @return iterable<BuildingAddress>
     */
    public function indexImportedSince(\DateTimeImmutable $timestamp): iterable;

    /**
     * Indexes a single building address for search.
     *
     * @throws BuildingAddressNotFoundException
     */
    public function indexById(Uuid $id): void;
}
