<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\Symfony\DependencyInjection\MeilisearchExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function prepareContainer(ContainerBuilder $container): void
    {
        $container->registerExtension(new MeilisearchExtension());
        parent::prepareContainer($container);
    }
}
