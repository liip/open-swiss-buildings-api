<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Symfony\Component\String\ByteString;

final class PostgreSQLCursorFetcher
{
    private function __construct() {}

    /**
     * @param array<string, mixed> $parameters
     *
     * @return iterable<array<string, mixed>>
     */
    public static function fetch(Connection $connection, string $sql, array $parameters = []): iterable
    {
        $cursorName = 'cursor_' . ByteString::fromRandom();

        $connection->beginTransaction();

        try {
            $connection->executeStatement("DECLARE {$cursorName} NO SCROLL CURSOR FOR {$sql}", $parameters);

            try {
                $stmt = $connection->prepare("FETCH NEXT FROM {$cursorName}");

                while ($row = $stmt->executeQuery()->fetchAssociative()) {
                    yield $row;
                }
            } finally {
                $connection->executeQuery("CLOSE {$cursorName}");
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
