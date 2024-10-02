<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Model\Job\WriteResolverTask;
use Symfony\Component\Uid\Uuid;

final class MemoryTaskRepository implements ResolverTaskWriteRepositoryInterface
{
    /**
     * @var list<WriteResolverTask>
     */
    public array $tasks;

    public function deleteByJobId(Uuid $jobId): int
    {
        $count = \count($this->tasks);
        $this->tasks = [];

        return $count;
    }

    public function store(iterable $tasks): void
    {
        foreach ($tasks as $task) {
            $this->tasks[] = $task;
        }
    }
}
