<?php

declare(strict_types=1);

namespace App\Domain\Resolving;

use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Event\ResolverJobHasBeenCreated;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class ResolverJobFactory implements ResolverJobFactoryInterface
{
    public function __construct(
        private ResolverJobWriteRepositoryInterface $writeRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function create(ResolverTypeEnum $type, $data, ?ResolverMetadata $metadata = null): ResolverJobIdentifier
    {
        $metadata = $metadata ?? new ResolverMetadata();

        $id = $this->writeRepository->add($type, $data, $metadata);
        $job = new ResolverJobIdentifier($id, $type);

        $this->eventDispatcher->dispatch(new ResolverJobHasBeenCreated($job));

        return $job;
    }
}
