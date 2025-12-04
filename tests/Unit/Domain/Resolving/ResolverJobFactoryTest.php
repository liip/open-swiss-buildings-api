<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Resolving;

use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Event\ResolverJobHasBeenCreated;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Domain\Resolving\ResolverJobFactory;
use App\Tests\Util\RecordingEventDispatcher;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[Small]
final class ResolverJobFactoryTest extends TestCase
{
    private MockObject&ResolverJobWriteRepositoryInterface $writeRepository;

    private RecordingEventDispatcher $eventDispatcher;

    private ResolverJobFactory $factory;

    protected function setUp(): void
    {
        $this->writeRepository = $this->createMock(ResolverJobWriteRepositoryInterface::class);
        $this->eventDispatcher = new RecordingEventDispatcher();
        $this->factory = new ResolverJobFactory($this->writeRepository, $this->eventDispatcher);
    }

    public function testJobCanBeCreated(): void
    {
        $data = fopen('php://memory', 'r+');
        $this->assertIsResource($data);

        $id = Uuid::v7();
        $this->writeRepository->expects($this->once())->method('add')->willReturn($id);

        $job = $this->factory->create(ResolverTypeEnum::BUILDING_IDS, $data);

        $this->assertSame($id, $job->id);
        $this->assertSame(ResolverTypeEnum::BUILDING_IDS, $job->type);

        $this->assertCount(1, $this->eventDispatcher->events);
        $this->assertInstanceOf(ResolverJobHasBeenCreated::class, $this->eventDispatcher->events[0]);
    }
}
