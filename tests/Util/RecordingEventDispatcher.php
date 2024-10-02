<?php

declare(strict_types=1);

namespace App\Tests\Util;

use Psr\EventDispatcher\EventDispatcherInterface;

final class RecordingEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var list<object>
     */
    public array $events;

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
