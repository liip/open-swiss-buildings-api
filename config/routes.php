<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('redirect_to_apidocs', '/')
        ->controller(RedirectController::class)
        ->defaults([
            'path' => '/doc',
            'permanent' => true,
        ])
    ;
};
