<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Contract\ResolverJobPrepareMessageDispatcherInterface;
use App\Application\Contract\ResolverJobResolveMessageDispatcherInterface;
use App\Application\Messaging\Message\ResolverPrepareJobMessage;
use App\Application\Messaging\Message\ResolverResolveJobMessage;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ResolverMessageDispatcher implements
    ResolverJobResolveMessageDispatcherInterface,
    ResolverJobPrepareMessageDispatcherInterface
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function dispatchJobForResolving(ResolverJobIdentifier $jobId): void
    {
        $message = new ResolverResolveJobMessage($jobId);
        $this->messageBus->dispatch($message);
    }

    public function dispatchJobForPreparation(ResolverJobIdentifier $jobId): void
    {
        $message = new ResolverPrepareJobMessage($jobId);
        $this->messageBus->dispatch($message);
    }
}
