<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Scheduler;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '1 day')]
final readonly class CleanupJobsHandler
{
    public function __construct(
        private ResolverJobReadRepositoryInterface $readRepository,
        private ResolverJobWriteRepositoryInterface $writeRepository,
        private ClockInterface $clock,
    ) {}

    public function __invoke(): void
    {
        foreach ($this->readRepository->getExpiredJobs($this->clock->now()) as $job) {
            $this->writeRepository->delete($job->id);
        }
    }
}
