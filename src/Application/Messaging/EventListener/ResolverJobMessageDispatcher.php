<?php

declare(strict_types=1);

namespace App\Application\Messaging\EventListener;

use App\Application\Contract\ResolverJobPrepareMessageDispatcherInterface;
use App\Application\Contract\ResolverJobResolveMessageDispatcherInterface;
use App\Domain\Resolving\Event\JobPreparationHasCompleted;
use App\Domain\Resolving\Event\ResolverJobHasBeenCreated;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ResolverJobMessageDispatcher
{
    private bool $enabled = true;

    public function __construct(
        private readonly ResolverJobResolveMessageDispatcherInterface $resolveQueuing,
        private readonly ResolverJobPrepareMessageDispatcherInterface $prepareQueuing,
    ) {}

    public function preventNextMessage(): void
    {
        $this->enabled = false;
    }

    #[AsEventListener]
    public function onCreated(ResolverJobHasBeenCreated $event): void
    {
        if ($this->enabled) {
            $this->prepareQueuing->dispatchJobForPreparation($event->job);
        }
        $this->enabled = true;
    }

    #[AsEventListener]
    public function onReady(JobPreparationHasCompleted $event): void
    {
        if ($this->enabled) {
            $this->resolveQueuing->dispatchJobForResolving($event->job);
        }
        $this->enabled = true;
    }
}
