<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use App\Domain\Resolving\Contract\AddressResolverInterface;
use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use App\Domain\Resolving\Model\Address\AddressResolvingData;
use App\Domain\Resolving\Model\Address\AddressResolvingResult;
use App\Infrastructure\Meilisearch\Contract\IndexProviderInterface;
use App\Infrastructure\Meilisearch\Contract\MultiSearcherInterface;
use App\Infrastructure\Meilisearch\Model\BuildingAddressEntity;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Exceptions\CommunicationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class MeilisearchAddressResolver implements AddressResolverInterface
{
    private const int SEARCH_BATCH_SIZE = 1000;

    public function __construct(
        private IndexProviderInterface $indexProvider,
        private MultiSearcherInterface $multiSearcher,
        #[Autowire(param: 'app.enable_search_engine')]
        private bool $enableResolving = true,
    ) {}

    public function resolveAddresses(iterable $addresses, bool $streetOnly = false): iterable
    {
        if (!$this->enableResolving) {
            return [];
        }

        $index = $this->indexProvider->getBuildingEntranceIndex();

        $batchQueries = $batchAddresses = [];
        foreach ($addresses as $address) {
            $batchAddresses[] = $address;

            $searchQuery = $this->buildSearchQuery($address, $streetOnly);
            $batchQueries[] = $searchQuery->setIndexUid((string) $index->getUid());

            if (\count($batchQueries) >= self::SEARCH_BATCH_SIZE) {
                yield from $this->doResolveAddresses($batchAddresses, $batchQueries);
                $batchQueries = $batchAddresses = [];
            }
        }

        if ([] !== $batchQueries) {
            yield from $this->doResolveAddresses($batchAddresses, $batchQueries);
        }
    }

    /**
     * @param list<AddressResolvingData> $batchAddresses
     * @param list<SearchQuery>          $batchQueries
     *
     * @return iterable<AddressResolvingResult>
     *
     * @throws RetryableResolvingErrorException
     */
    private function doResolveAddresses(array $batchAddresses, array $batchQueries): iterable
    {
        try {
            $searchResults = $this->multiSearcher->multiSearch($batchQueries);
        } catch (CommunicationException $exception) {
            throw RetryableResolvingErrorException::fromException($exception);
        }

        foreach ($searchResults as $i => $searchResult) {
            $result = null;

            foreach ($searchResult->getHits() as $result) {
                break;
            }
            if (null === $result) {
                yield AddressResolvingResult::notMatched($batchAddresses[$i]);
            } else {
                $buildingAddress = BuildingAddressEntity::hydrateScoredBuildingAddress($result);

                yield AddressResolvingResult::matched(
                    $batchAddresses[$i],
                    $buildingAddress->score,
                    $buildingAddress->buildingAddress->buildingId,
                    $buildingAddress->buildingAddress->entranceId,
                    $buildingAddress->buildingAddress->streetId,
                    '' !== $buildingAddress->matchingHighlight ? $buildingAddress->matchingHighlight : null,
                );
            }
        }
    }

    private function buildSearchQuery(AddressResolvingData $address, bool $streetOnly): SearchQuery
    {
        $searchQuery = new SearchQuery();
        $searchQuery->setQuery($streetOnly ? $address->getStreet() : $address->getAddress());
        $searchQuery->setLimit(1);
        $searchQuery->setShowRankingScore(true);
        $searchQuery->setAttributesToRetrieve(BuildingAddressEntity::HYDRATION_FIELDS);
        $searchQuery->setAttributesToSearchOn(BuildingAddressEntity::SEARCHABLE_FIELDS);
        $searchQuery->setAttributesToHighlight(BuildingAddressEntity::SEARCHABLE_HIGHLIGHTED_FIELDS);

        return $searchQuery;
    }
}
