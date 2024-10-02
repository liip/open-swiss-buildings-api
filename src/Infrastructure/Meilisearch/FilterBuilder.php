<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

final class FilterBuilder
{
    /**
     * @param non-empty-string       $field
     * @param list<non-empty-string> $values
     *
     * @return non-empty-string
     */
    public static function buildListFilter(string $field, array $values): string
    {
        return sprintf('%s IN ["%s"]', $field, implode('","', $values));
    }

    /**
     * @param list<non-empty-string> $filters
     */
    public static function mergeFilters(array $filters): string
    {
        return implode(' OR ', $filters);
    }

    /** @codeCoverageIgnore  */
    private function __construct() {}
}
