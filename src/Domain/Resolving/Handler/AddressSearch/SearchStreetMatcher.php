<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\Resolving\Contract\AddressResolverInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressStreetWriteRepositoryInterface;
use App\Domain\Resolving\Model\Address\AddressResolvingData;
use App\Domain\Resolving\Model\Address\AddressResolvingResult;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\WriteResolverAddressStreet;

final readonly class SearchStreetMatcher
{
    public const string TYPE_STREET_SEARCH = 'streetSearch';

    public function __construct(
        private ResolverAddressStreetWriteRepositoryInterface $streetMatchesRepository,
        private AddressResolverInterface $addressResolver,
        private ResolverAddressReadRepositoryInterface $addressRepository,
    ) {}

    /**
     * @param int<0, 100> $minConfidence
     * @param int<0, 100> $maxConfidence
     */
    public function matchStreetsBySearch(ResolverJobIdentifier $job, int $maxConfidence, int $minConfidence): void
    {
        $this->streetMatchesRepository->store(
            $this->transformResolvingResultsToStreets(
                $this->addressResolver->resolveAddresses(
                    $this->getStreetsToResolve($job),
                    true,
                ),
                $maxConfidence,
                $minConfidence,
            ),
        );
    }

    /**
     * @param iterable<AddressResolvingResult> $resolvingResults
     * @param int<0, 100>                      $maxConfidence
     * @param int<0, 100>                      $minConfidence
     *
     * @return iterable<WriteResolverAddressStreet>
     */
    private function transformResolvingResultsToStreets(
        iterable $resolvingResults,
        int $maxConfidence = 100,
        int $minConfidence = 0,
    ): iterable {
        foreach ($resolvingResults as $resolvingResult) {
            $confidence = min($resolvingResult->confidence ?? 0, $maxConfidence);

            if (null !== $resolvingResult->matchingStreetId && $confidence >= $minConfidence) {
                yield new WriteResolverAddressStreet(
                    addressId: $resolvingResult->address->referenceId,
                    streetId: $resolvingResult->matchingStreetId,
                    confidence: $confidence,
                    matchType: self::TYPE_STREET_SEARCH,
                );
            }
        }
    }

    /**
     * @return iterable<AddressResolvingData>
     */
    private function getStreetsToResolve(ResolverJobIdentifier $job): iterable
    {
        foreach ($this->addressRepository->getNonMatchedAddressesWithoutStreet($job->id) as $address) {
            // Skip addresses without a street, ZIP code or locality
            if (\in_array(null, [$address->street, $address->postalCode, $address->locality], true)) {
                continue;
            }

            yield new AddressResolvingData(
                referenceId: $address->id,
                street: $address->street,
                postalCode: $address->postalCode,
                locality: $address->locality,
                additionalData: $address->additionalData,
            );
        }
    }
}
