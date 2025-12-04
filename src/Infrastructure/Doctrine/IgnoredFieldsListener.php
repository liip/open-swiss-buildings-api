<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Jsor\Doctrine\PostGIS\Driver\PostGISPlatform;

/**
 * Ignores certain things from Doctrine, which are handled manually.
 *
 * Idea taken from https://gist.github.com/Brewal/4a623208e7cd60c4dfb5ab9ab56bcb2e
 */
final class IgnoredFieldsListener
{
    private ?Schema $schema = null;

    /**
     * @var AbstractSchemaManager<PostGISPlatform>|null
     */
    private ?AbstractSchemaManager $schemaManager = null;

    /**
     * @param array{string: list<string>} $ignoredIndexes
     */
    public function __construct(private readonly array $ignoredIndexes) {}

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $this->schema = $args->getSchema();
        $this->schemaManager = $args->getEntityManager()->getConnection()->createSchemaManager();

        foreach ($this->ignoredIndexes as $tableToFilter => $indexesToIgnore) {
            $this->ignoreIndexes($tableToFilter, $indexesToIgnore);
        }
    }

    /**
     * @param list<string> $indexesToIgnore
     */
    private function ignoreIndexes(string $table, array $indexesToIgnore): void
    {
        $indexes = $this->getSchemaManager()->listTableIndexes($table);

        $handledIndexes = [];
        foreach ($indexes as $index) {
            if (!\in_array($index->getName(), $indexesToIgnore, true)) {
                continue;
            }

            $handledIndexes[] = $index->getName();
            $indexColumns = $index->getColumns();

            if ($index->isUnique()) {
                $this->getSchema()->getTable($table)->addUniqueIndex(
                    $indexColumns,
                    $index->getName(),
                    $index->getOptions(),
                );

                continue;
            }

            $this->getSchema()->getTable($table)->addIndex(
                $indexColumns,
                $index->getName(),
                $index->getFlags(),
                $index->getOptions(),
            );
        }

        $missingIndexes = array_diff($indexesToIgnore, $handledIndexes);
        if ([] !== $missingIndexes) {
            throw new \LogicException('Indexes ' . implode(', ', $missingIndexes) . " should exist on table {$table}, but they don't, please check the Doctrine migrations");
        }
    }

    private function getSchema(): Schema
    {
        if (null === $this->schema) {
            throw new \LogicException('schema should be set in postGenerateSchema');
        }

        return $this->schema;
    }

    /** @phpstan-ignore-next-line */
    private function getSchemaManager(): AbstractSchemaManager
    {
        if (null === $this->schemaManager) {
            throw new \LogicException('schemaManager should be set in postGenerateSchema');
        }

        return $this->schemaManager;
    }
}
