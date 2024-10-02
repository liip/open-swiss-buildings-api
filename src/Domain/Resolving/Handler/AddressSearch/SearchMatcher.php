<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\Resolving\Contract\AddressResolverInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressMatchWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressReadRepositoryInterface;
use App\Domain\Resolving\Model\Address\AddressResolvingData;
use App\Domain\Resolving\Model\Address\AddressResolvingResult;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\WriteResolverAddressMatch;

final readonly class SearchMatcher
{
    public const string TYPE_SEARCH = 'search';

    public function __construct(
        private ResolverAddressReadRepositoryInterface $addressRepository,
        private ResolverAddressMatchWriteRepositoryInterface $matchRepository,
        private AddressResolverInterface $addressResolver,
    ) {}

    /**
     * @param int<0, 100> $minConfidence
     * @param int<0, 100> $maxConfidence
     */
    public function matchBySearch(ResolverJobIdentifier $job, int $maxConfidence, int $minConfidence): void
    {
        $this->matchRepository->store(
            $this->transformResolvingResultsToMatches(
                $this->addressResolver->resolveAddresses(
                    $this->getAddressesToResolveBySearch($job),
                ),
                $maxConfidence,
                $minConfidence,
            ),
        );
    }

    /**
     * @return iterable<AddressResolvingData>
     */
    private function getAddressesToResolveBySearch(ResolverJobIdentifier $job): iterable
    {
        foreach ($this->addressRepository->getNonMatchedAddresses($job->id) as $address) {
            // Skip addresses without a street, ZIP code or locality
            if (null === $address->street || null === $address->postalCode || null === $address->locality) {
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

    /**
     * @param iterable<AddressResolvingResult> $resolvingResults
     * @param int<0, 100>                      $maxConfidence
     * @param int<0, 100>                      $minConfidence
     *
     * @return iterable<WriteResolverAddressMatch>
     */
    private function transformResolvingResultsToMatches(
        iterable $resolvingResults,
        int $maxConfidence = 100,
        int $minConfidence = 0,
    ): iterable {
        foreach ($resolvingResults as $resolvingResult) {
            $confidence = min($resolvingResult->confidence ?? 0, $maxConfidence);

            if (null !== $resolvingResult->matchingBuildingId && $confidence >= $minConfidence) {
                yield new WriteResolverAddressMatch(
                    id: $resolvingResult->address->referenceId,
                    confidence: $confidence,
                    matchType: self::TYPE_SEARCH,
                    matchingBuildingId: $resolvingResult->matchingBuildingId,
                    matchingEntranceId: $resolvingResult->matchingEntranceId,
                    additionalData: $resolvingResult->address->additionalData,
                );
            }
        }
    }
}
