<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract;

use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use App\Domain\Resolving\Model\Address\AddressResolvingData;
use App\Domain\Resolving\Model\Address\AddressResolvingResult;

interface AddressResolverInterface
{
    /**
     * Searches for the given addresses and returns a result.
     *
     * @param iterable<AddressResolvingData> $addresses
     *
     * @return iterable<AddressResolvingResult>
     *
     * @throws RetryableResolvingErrorException
     */
    public function resolveAddresses(iterable $addresses, bool $streetOnly = false): iterable;
}
