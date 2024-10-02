<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use App\Infrastructure\Meilisearch\Contract\MultiSearcherInterface;
use Meilisearch\Client;
use Meilisearch\Search\SearchResult;

final readonly class MeilisearchMultiSearcher implements MultiSearcherInterface
{
    public function __construct(
        private Client $client,
    ) {}

    public function multiSearch(array $queries): array
    {
        $results = [];
        $response = $this->client->multiSearch($queries);
        foreach ($response['results'] as $result) {
            $results[] = new SearchResult($result);
        }

        return $results;
    }
}
