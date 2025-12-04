<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /* @phpstan-ignore missingType.generics */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('meilisearch');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('default_connection')
                ->isRequired()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
