<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving;

use App\Domain\Resolving\Contract\Job\TaskResolverInterface;
use App\Domain\Resolving\Event\JobResolvingHasCompleted;
use App\Domain\Resolving\Event\JobResolvingHasFailed;
use App\Domain\Resolving\Event\JobResolvingHasStarted;
use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Domain\Resolving\TaskResolvingHandler;
use App\Tests\Util\RecordingEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

#[Small]
#[CoversClass(TaskResolvingHandler::class)]
final class TaskResolvingHandlerTest extends TestCase
{
    private RecordingEventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = new RecordingEventDispatcher();
    }

    public function testJobIsHandled(): void
    {
        $taskResolver = new class () implements TaskResolverInterface {
            public bool $handled = false;

            public function canResolveTasks(ResolverTypeEnum $type): bool
            {
                return true;
            }

            public function resolveTasks(ResolverJobIdentifier $job): void
            {
                $this->handled = true;
            }
        };

        $taskResolverHandler = new TaskResolvingHandler([$taskResolver], new NullLogger(), $this->eventDispatcher);

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $taskResolverHandler->handleResolving($job);

        $this->assertTrue($taskResolver->handled);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobResolvingHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobResolvingHasCompleted::class, $this->eventDispatcher->events[1]);
    }

    public function testNoHandlerThrowsException(): void
    {
        $taskResolver = new class () implements TaskResolverInterface {
            public bool $handled = false;

            public function canResolveTasks(ResolverTypeEnum $type): bool
            {
                // Returns false to not handle the job
                return false;
            }

            public function resolveTasks(ResolverJobIdentifier $job): void
            {
                $this->handled = true;
            }
        };

        $taskResolverHandler = new TaskResolvingHandler([$taskResolver], new NullLogger(), $this->eventDispatcher);

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $taskResolverHandler->handleResolving($job);

        $this->assertFalse($taskResolver->handled);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobResolvingHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobResolvingHasFailed::class, $this->eventDispatcher->events[1]);
    }

    public function testHandlerErrorThrowsException(): void
    {
        $taskResolver = new class () implements TaskResolverInterface {
            public bool $handled = false;

            public function canResolveTasks(ResolverTypeEnum $type): bool
            {
                return true;
            }

            public function resolveTasks(ResolverJobIdentifier $job): void
            {
                $this->handled = true;

                throw new \Exception('Test error');
            }
        };

        $taskResolverHandler = new TaskResolvingHandler([$taskResolver], new NullLogger(), $this->eventDispatcher);

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $taskResolverHandler->handleResolving($job);

        $this->assertTrue($taskResolver->handled);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobResolvingHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobResolvingHasFailed::class, $this->eventDispatcher->events[1]);
    }

    public function testHandlerRetriesWhenRetryableException(): void
    {
        $taskResolver = new class () implements TaskResolverInterface {
            public bool $handled = false;

            public function canResolveTasks(ResolverTypeEnum $type): bool
            {
                return true;
            }

            public function resolveTasks(ResolverJobIdentifier $job): void
            {
                $this->handled = true;

                throw new RetryableResolvingErrorException('Try me again!');
            }
        };

        $taskResolverHandler = new TaskResolvingHandler([$taskResolver], new NullLogger(), $this->eventDispatcher);

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $exceptionIsThrown = false;
        try {
            $taskResolverHandler->handleResolving($job);
        } catch (RetryableResolvingErrorException $exception) {
            $exceptionIsThrown = true;
        }

        $this->assertTrue($exceptionIsThrown);

        $this->assertTrue($taskResolver->handled);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobResolvingHasStarted::class, $this->eventDispatcher->events[0]);

        $this->assertInstanceOf(JobResolvingHasFailed::class, $this->eventDispatcher->events[1]);
        $this->assertTrue($this->eventDispatcher->events[1]->failure->retryable);
    }
}
