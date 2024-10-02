<?php

declare(strict_types=1);

namespace App\Domain\Resolving\EventListener;

use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Event\JobPreparationHasCompleted;
use App\Domain\Resolving\Event\JobPreparationHasStarted;
use App\Domain\Resolving\Event\JobResolvingHasCompleted;
use App\Domain\Resolving\Event\JobResolvingHasFailed;
use App\Domain\Resolving\Event\JobResolvingHasStarted;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class DoctrineResolverJobStateUpdater
{
    public function __construct(
        private ResolverJobWriteRepositoryInterface $repository,
    ) {}

    #[AsEventListener(priority: 10)]
    public function onPreparationStarted(JobPreparationHasStarted $event): void
    {
        $this->repository->markJobAsPreparing($event->job->id);
    }

    #[AsEventListener(priority: 10)]
    public function onPreparationCompleted(JobPreparationHasCompleted $event): void
    {
        $this->repository->markJobAsReady($event->job->id);
    }

    #[AsEventListener(priority: 10)]
    public function onResolvingStarted(JobResolvingHasStarted $event): void
    {
        $this->repository->markJobAsResolving($event->job->id);
    }

    #[AsEventListener(priority: 10)]
    public function onCompleted(JobResolvingHasCompleted $event): void
    {
        $this->repository->markJobAsCompleted($event->job->id);
    }

    #[AsEventListener(priority: 10)]
    public function onFailed(JobResolvingHasFailed $event): void
    {
        if ($event->failure->retryable) {
            $this->repository->markJobAsTemporarilyFailed($event->job->id, $event->failure);

            return;
        }

        $this->repository->markJobAsFailed($event->job->id, $event->failure);
    }
}
