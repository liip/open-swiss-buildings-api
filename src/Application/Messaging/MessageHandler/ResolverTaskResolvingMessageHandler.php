<?php

declare(strict_types=1);

namespace App\Application\Messaging\MessageHandler;

use App\Application\Messaging\Message\ResolverResolveJobMessage;
use App\Domain\Resolving\Contract\TaskResolvingHandlerInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ResolverTaskResolvingMessageHandler
{
    public function __construct(
        private TaskResolvingHandlerInterface $resolverHandler,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ResolverResolveJobMessage $message): void
    {
        try {
            $this->resolverHandler->handleResolving($message->job);
        } catch (ResolverJobNotFoundException $e) {
            $this->logger->warning('Resolver job with ID {job_id} was not found!', ['job_id' => $message->job->id, 'exception' => $e]);
        } catch (RetryableResolvingErrorException $e) {
            $this->logger->warning('Resolver job with ID {job_id} failed, and should be retried!', ['job_id' => $message->job->id, 'exception' => $e]);

            throw $e;
        }
    }
}
