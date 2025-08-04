<?php

declare(strict_types=1);

use App\Application\Messaging\Message\AsyncDefaultMessage;
use App\Application\Messaging\Message\AsyncResolveMessage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $framework, ContainerConfigurator $container): void {
    $messenger = $framework->messenger();
    $messenger->failureTransport('failed');

    // @todo: remove the annotations when https://github.com/symfony/symfony/pull/54008 is fixed
    $asyncDefault = $messenger->transport('async');
    $asyncResolve = $messenger->transport('resolve');
    $failed = $messenger->transport('failed');

    if ('test' === $container->env()) {
        // Use in-memory transport for tests, and don't handle messages asynchronously
        $asyncDefault->dsn('in-memory://');
        $asyncResolve->dsn('in-memory://');
        $failed->dsn('in-memory://');

        return;
    }

    $asyncResolveRouting = $messenger->routing(AsyncResolveMessage::class);
    $asyncResolveRouting->senders(['resolve']);
    $asyncResolve->dsn(env('MESSENGER_TRANSPORT_DSN'))
        ->options(['queue_name' => 'resolve'])
        ->retryStrategy()
        ->maxRetries(3)
        // 30s delay (in milliseconds) for the first retry
        ->delay(30_000)
        // causes the delay to be higher for each retry
        // 30s → 2m → 10m
        ->multiplier(5)
        // Max time (in ms) that a retry should ever be delayed: 20 minutes
        ->maxDelay(15 * 60_000)
        // applies randomness to the delay that can prevent the thundering herd effect
        // the value (between 0 and 1.0) is the percentage of 'delay' that will be added/subtracted
        ->jitter(0.2)
    ;

    $asyncDefaultRouting = $messenger->routing(AsyncDefaultMessage::class);
    $asyncDefaultRouting->senders(['async']);
    $asyncDefault->dsn(env('MESSENGER_TRANSPORT_DSN'))
        ->options(['queue_name' => 'async'])
    ;

    $failed->dsn(env('MESSENGER_TRANSPORT_DSN'))
        ->options(['queue_name' => 'failed'])
    ;
};
