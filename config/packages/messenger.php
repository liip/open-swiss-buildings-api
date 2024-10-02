<?php

declare(strict_types=1);

use App\Application\Messaging\Message\AsyncMessage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\Framework\Messenger\RoutingConfig;
use Symfony\Config\Framework\Messenger\TransportConfig;
use Symfony\Config\FrameworkConfig;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $framework, ContainerConfigurator $container): void {
    $messenger = $framework->messenger();
    $messenger->failureTransport('failed');

    $async = $messenger->transport('async');
    // @todo: remove when https://github.com/symfony/symfony/pull/54008 is fixed
    assert($async instanceof TransportConfig);

    $failed = $messenger->transport('failed');
    // @todo: remove when https://github.com/symfony/symfony/pull/54008 is fixed
    assert($failed instanceof TransportConfig);

    if ('test' === $container->env()) {
        // Use in-memory transport for tests, and don't handle messages asynchronously
        $async->dsn('in-memory://');
        $failed->dsn('in-memory://');

        return;
    }

    $async->dsn(env('MESSENGER_TRANSPORT_DSN'))
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

    $failed->dsn('doctrine://default?queue_name=failed');

    $sender = $messenger->routing(AsyncMessage::class);
    // @todo: remove when https://github.com/symfony/symfony/pull/54008 is fixed
    assert($sender instanceof RoutingConfig);
    $sender->senders(['async']);
};
