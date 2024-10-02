<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving;

use App\Domain\Resolving\Contract\Data\ResolverJobRawDataRepositoryInterface;
use App\Domain\Resolving\Contract\Job\JobPreparerInterface;
use App\Domain\Resolving\Event\JobPreparationHasCompleted;
use App\Domain\Resolving\Event\JobPreparationHasStarted;
use App\Domain\Resolving\Event\JobResolvingHasFailed;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\JobPreparationHandler;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Tests\Util\RecordingEventDispatcher;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[Small]
final class JobPreparationHandlerTest extends TestCase
{
    private ResolverJobRawDataRepositoryInterface&Stub $rawDataRepository;
    private RecordingEventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->rawDataRepository = $this->createStub(ResolverJobRawDataRepositoryInterface::class);
        $this->eventDispatcher = new RecordingEventDispatcher();
    }

    public function testJobIsPrepared(): void
    {
        $preparer = new class () implements JobPreparerInterface {
            public bool $prepared = false;

            public function canPrepareJob(ResolverTypeEnum $type): bool
            {
                return true;
            }

            public function prepareJob(ResolverJobRawData $jobData): void
            {
                $this->prepared = true;
            }
        };

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $this->rawDataRepository->method('getRawData')->willReturn(new ResolverJobRawData($job->id, $job->type, $this->createResource(), new ResolverMetadata()));

        $jobPreparerHandler = new JobPreparationHandler($this->rawDataRepository, [$preparer], $this->eventDispatcher);

        $jobPreparerHandler->handlePreparation($job);

        $this->assertTrue($preparer->prepared);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobPreparationHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobPreparationHasCompleted::class, $this->eventDispatcher->events[1]);
    }

    public function testNoPreparerThrowsException(): void
    {
        $preparer = new class () implements JobPreparerInterface {
            public bool $prepared = false;

            public function canPrepareJob(ResolverTypeEnum $type): bool
            {
                // Returns false to not prepare the job
                return false;
            }

            public function prepareJob(ResolverJobRawData $jobData): void
            {
                $this->prepared = true;
            }
        };

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $this->rawDataRepository->method('getRawData')->willReturn(new ResolverJobRawData($job->id, $job->type, $this->createResource(), new ResolverMetadata()));

        $jobPreparerHandler = new JobPreparationHandler($this->rawDataRepository, [$preparer], $this->eventDispatcher);

        $jobPreparerHandler->handlePreparation($job);

        $this->assertFalse($preparer->prepared);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobPreparationHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobResolvingHasFailed::class, $this->eventDispatcher->events[1]);
    }

    public function testPreparerErrorThrowsException(): void
    {
        $preparer = new class () implements JobPreparerInterface {
            public bool $prepared = false;

            public function canPrepareJob(ResolverTypeEnum $type): bool
            {
                return true;
            }

            public function prepareJob(ResolverJobRawData $jobData): void
            {
                $this->prepared = true;

                throw new InvalidInputDataException('Test error');
            }
        };

        $job = new ResolverJobIdentifier(Uuid::v7(), ResolverTypeEnum::BUILDING_IDS);

        $this->rawDataRepository->method('getRawData')->willReturn(new ResolverJobRawData($job->id, $job->type, $this->createResource(), new ResolverMetadata()));

        $jobPreparerHandler = new JobPreparationHandler($this->rawDataRepository, [$preparer], $this->eventDispatcher);

        $jobPreparerHandler->handlePreparation($job);

        $this->assertTrue($preparer->prepared);

        $this->assertCount(2, $this->eventDispatcher->events);
        $this->assertInstanceOf(JobPreparationHasStarted::class, $this->eventDispatcher->events[0]);
        $this->assertInstanceOf(JobResolvingHasFailed::class, $this->eventDispatcher->events[1]);
    }

    /**
     * @return resource
     */
    private function createResource()
    {
        $resource = fopen('php://memory', 'r+');
        $this->assertIsResource($resource);

        return $resource;
    }
}
