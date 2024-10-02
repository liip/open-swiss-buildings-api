<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Contract;

use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Search\SearchResult;

interface MultiSearcherInterface
{
    /**
     * @param list<SearchQuery> $queries
     *
     * @return list<SearchResult>
     */
    public function multiSearch(array $queries): array;
}
