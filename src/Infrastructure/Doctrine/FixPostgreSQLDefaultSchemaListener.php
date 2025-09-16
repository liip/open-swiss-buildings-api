<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Hack to avoid Doctrine issue with trying to recreate schema on down migration.
 *
 * https://gist.github.com/vudaltsov/ec01012d3fe27c9eed59aa7fd9089cf7
 */
#[When('dev')]
#[When('test')]
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema, connection: 'default')]
final class FixPostgreSQLDefaultSchemaListener
{
    public function __invoke(GenerateSchemaEventArgs $args): void
    {
        $schemaManager = $args
            ->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
        ;

        if (!$schemaManager instanceof PostgreSQLSchemaManager) {
            return;
        }

        $schema = $args->getSchema();

        foreach ($schemaManager->listSchemaNames() as $namespace) {
            if (!$schema->hasNamespace($namespace) && 'public' !== $namespace) {
                $schema->createNamespace($namespace);
            }
        }
    }
}
