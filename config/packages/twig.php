<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function ($twig, ContainerConfigurator $container): void {
    $twig->fileNamePattern('*.twig');

    if ('test' === $container->env()) {
        $twig->strictVariables(true);
    }
};
