<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\DependencyInjection;

use App\Infrastructure\Meilisearch\MeilisearchClientFactory;
use Meilisearch\Client;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class MeilisearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $clientDefinition = new Definition(Client::class);
        $clientDefinition->setPublic(true);
        $clientDefinition->setFactory([MeilisearchClientFactory::class, 'fromDsn']);
        $clientDefinition->setArguments([$config['default_connection']]);

        $container->setDefinition('meilisearch.client.default', $clientDefinition);
        $container->setAlias(Client::class, new Alias('meilisearch.client.default', true));
    }
}
