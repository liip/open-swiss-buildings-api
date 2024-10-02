<?php

declare(strict_types=1);

namespace App\Application\Messaging\MessageHandler;

use App\Application\Messaging\Message\ResolverPrepareJobMessage;
use App\Domain\Resolving\Contract\JobPreparationHandlerInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ResolverJobPreparingMessageHandler
{
    public function __construct(
        private JobPreparationHandlerInterface $preparer,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ResolverPrepareJobMessage $message): void
    {
        try {
            $this->preparer->handlePreparation($message->job);
        } catch (ResolverJobNotFoundException $e) {
            $this->logger->warning('Resolver job with ID {job_id} was not found!', ['job_id' => $message->job->id, 'exception' => $e]);
        }
    }
}
