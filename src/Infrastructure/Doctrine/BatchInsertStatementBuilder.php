<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

final class BatchInsertStatementBuilder
{
    private function __construct() {}

    /**
     * @param array<non-empty-string, non-empty-string> $columnDefinitions
     * @param list<non-empty-string>                    $conflictColumns
     * @param list<non-empty-string>                    $conflictUpdates
     *
     * @return non-empty-string
     */
    public static function generate(
        string $tableName,
        int $count,
        array $columnDefinitions,
        array $conflictColumns = [],
        array $conflictUpdates = [],
    ): string {
        $columns = implode(', ', array_keys($columnDefinitions));
        $placeholders = array_values($columnDefinitions);

        $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ";
        for ($i = 1; $i <= $count; ++$i) {
            if ($i > 1) {
                $sql .= ', ';
            }
            $sql .= '(' . implode(',', array_map(static fn(string $placeholder): string => str_replace('%i%', (string) $i, $placeholder), $placeholders)) . ')';
        }

        if ([] !== $conflictColumns && [] !== $conflictUpdates) {
            $cColumns = implode(', ', $conflictColumns);
            $cUpdates = implode(', ', array_map(static fn(string $c): string => "{$c} = excluded.{$c}", $conflictUpdates));

            $sql .= " ON CONFLICT ({$cColumns}) DO UPDATE SET {$cUpdates}";
        }

        return $sql;
    }
}
