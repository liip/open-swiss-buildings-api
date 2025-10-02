<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use App\Domain\AddressSearch\Contract\AddressSearchReadRepositoryInterface;
use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Infrastructure\Meilisearch\Contract\IndexProviderInterface;
use App\Infrastructure\Meilisearch\Model\BuildingAddressEntity;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Exceptions\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

final readonly class MeilisearchAddressSearchRepository implements
    AddressSearchWriteRepositoryInterface,
    AddressSearchReadRepositoryInterface
{
    private const int INDEX_BATCH_SIZE = 500;

    public function __construct(
        private IndexProviderInterface $indexProvider,
        private LoggerInterface $logger,
    ) {}

    public function indexBuildingAddresses(iterable $buildingAddresses): iterable
    {
        $documents = [];
        foreach ($buildingAddresses as $buildingAddress) {
            // We directly use json_encode() as using a SerializerInterface showed memory leaks
            $documents[] = json_encode(BuildingAddressEntity::buildingAddressAsArray($buildingAddress), \JSON_THROW_ON_ERROR);

            if (self::INDEX_BATCH_SIZE === \count($documents)) {
                $index = $this->indexProvider->getBuildingEntranceIndex();
                $index->addDocumentsNdjson(implode("\n", $documents), 'id');
                $documents = [];
            }

            yield $buildingAddress;
        }

        if ([] !== $documents) {
            $index = $this->indexProvider->getBuildingEntranceIndex();
            $index->addDocumentsNdjson(implode("\n", $documents), 'id');
        }
    }

    public function searchAddress(AddressSearch $addressSearch, bool $debug = false): iterable
    {
        $index = $this->indexProvider->getBuildingEntranceIndex();
        $query = $addressSearch->filterByQuery;
        $params = [
            // Note: no sort should be added here, as we have a min-score filtering implemented later on
            // which rely on the score being usd as main sorting criteria.
            'limit' => $addressSearch->limit ?? AddressSearch::DEFAULT_LIMIT,
            // Define if "all" entered keywords MUST be matched in the results
            // 'matchingStrategy' => 'all',
            // Return the field and the position where a match was found (for debugging)
            // 'showMatchesPosition' => true,
            'showRankingScore' => true,
            'showRankingScoreDetails' => $debug,
            'attributesToRetrieve' => BuildingAddressEntity::HYDRATION_FIELDS,
            'attributesToSearchOn' => BuildingAddressEntity::SEARCHABLE_FIELDS,
            'attributesToHighlight' => BuildingAddressEntity::SEARCHABLE_HIGHLIGHTED_FIELDS,
        ];
        $options = [];

        $filters = null;
        if (null !== $addressSearch->filterByBuildingIds) {
            $filters[] = FilterBuilder::buildListFilter(BuildingAddressEntity::FIELD_BUILDING_ID, $addressSearch->filterByBuildingIds);
        }
        if (null !== $addressSearch->filterByIds) {
            $filters[] = FilterBuilder::buildListFilter(BuildingAddressEntity::FIELD_ID, $addressSearch->filterByIds);
        }
        if (null !== $addressSearch->filterByCountryCodes) {
            $codes = array_map(static fn(CountryCodeEnum $enum) => $enum->value, $addressSearch->filterByCountryCodes);
            $filters[] = FilterBuilder::buildListFilter(BuildingAddressEntity::FIELD_COUNTRY_CODE, $codes);
        }

        $params['filter'] = null === $filters ? null : FilterBuilder::mergeOrFilters($filters);

        // If we have a filter, but no query provided: we use a wildcard to match all entries and
        // then have Meilisearch apply the filter
        if (null === $query && null !== $filters) {
            $query = '*';
        }

        $result = $index->search($query, $params, $options);

        foreach ($result->getHits() as $hit) {
            $score = BuildingAddressEntity::extractScore($hit);
            if (null !== $addressSearch->minScore && $addressSearch->minScore > $score) {
                // If we reach the min-score, we stop and stop returning results as the subsequent ones
                // will have a lower score: this is enforced by the results being returned by Meilisearch
                // sorted by their score in a descending order.
                break;
            }

            yield BuildingAddressEntity::hydrateScoredBuildingAddress($hit);
        }
    }

    public function countIndexedAddresses(): int
    {
        $index = $this->indexProvider->getBuildingEntranceIndex();

        return $index->stats()['numberOfDocuments'] ?? 0;
    }

    public function findAddress(UuidV7 $id): ?BuildingAddress
    {
        $identifier = BuildingAddress::extractIdentifier($id);

        $index = $this->indexProvider->getBuildingEntranceIndex();
        try {
            $result = $index->getDocument($identifier, BuildingAddressEntity::HYDRATION_FIELDS);
            if (!\is_array($result)) {
                return null;
            }
        } catch (ApiException $exception) {
            if (404 === $exception->httpStatus) {
                return null;
            }
            throw $exception;
        }

        return BuildingAddressEntity::hydrateBuildingAddress($result);
    }

    public function findAddresses(Pagination $pagination): iterable
    {
        $index = $this->indexProvider->getBuildingEntranceIndex();

        $query = (new DocumentsQuery())
            ->setLimit($pagination->limit)
            ->setOffset($pagination->offset)
            ->setFields(BuildingAddressEntity::HYDRATION_FIELDS)
        ;
        $result = $index->getDocuments($query);

        foreach ($result->getResults() as $result) {
            yield BuildingAddressEntity::hydrateBuildingAddress($result);
        }
    }

    public function deleteByImportedAtBefore(\DateTimeImmutable $dateTime, ?CountryCodeEnum $countryCode = null): void
    {
        $filters = [];
        $filters[] = FilterBuilder::mergeOrFilters([
            BuildingAddressEntity::FIELD_IMPORTED_AT . ' < ' . $dateTime->format('Ymd'),
            BuildingAddressEntity::FIELD_IMPORTED_AT . ' NOT EXISTS',
        ]);
        if ($countryCode instanceof CountryCodeEnum) {
            $filters[] = BuildingAddressEntity::FIELD_COUNTRY_CODE . ' = ' . $countryCode->value;
        }

        $this->deleteByFilter(FilterBuilder::mergeAndFilters($filters));
    }

    public function deleteByIds(array $ids): void
    {
        $ids = array_values(array_filter($ids));
        if ([] === $ids) {
            return;
        }
        $this->deleteByFilter(FilterBuilder::buildListFilter(BuildingAddressEntity::FIELD_ID, $ids));
    }

    private function deleteByFilter(string $filter): void
    {
        $index = $this->indexProvider->getBuildingEntranceIndex();
        $result = $index->deleteDocuments(['filter' => $filter]);

        $this->logger->info('Queued deletion of documents, taskUid={taskUid}', [
            'filter' => $filter,
            'taskUid' => $result['taskUid'],
        ]);
    }
}
