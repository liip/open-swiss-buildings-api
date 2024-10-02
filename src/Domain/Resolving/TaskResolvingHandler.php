<?php

declare(strict_types=1);

namespace App\Domain\Resolving;

use App\Domain\Resolving\Contract\Job\TaskResolverInterface;
use App\Domain\Resolving\Contract\TaskResolvingHandlerInterface;
use App\Domain\Resolving\Event\JobResolvingHasCompleted;
use App\Domain\Resolving\Event\JobResolvingHasFailed;
use App\Domain\Resolving\Event\JobResolvingHasStarted;
use App\Domain\Resolving\Exception\NoTaskResolvingHandlerFoundException;
use App\Domain\Resolving\Exception\RetryableResolvingErrorException;
use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Failure\ResolverJobFailureEnum;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class TaskResolvingHandler implements TaskResolvingHandlerInterface
{
    public function __construct(
        /**
         * @var list<TaskResolverInterface>
         */
        #[AutowireIterator(TaskResolverInterface::class)]
        private iterable $taskResolvers,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handleResolving(ResolverJobIdentifier $job): void
    {
        $this->eventDispatcher->dispatch(new JobResolvingHasStarted($job));

        try {
            $this->getTaskResolver($job)->resolveTasks($job);
        } catch (NoTaskResolvingHandlerFoundException $e) {
            $this->logger->error('Error wile resolving Job: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->eventDispatcher->dispatch(new JobResolvingHasFailed(
                $job,
                ResolverJobFailure::fromException(ResolverJobFailureEnum::NO_TASK_RESOLVER, $e),
            ));

            return;
        } catch (RetryableResolvingErrorException $e) {
            $this->logger->error('Retryable error while resolving Job: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            $this->eventDispatcher->dispatch(JobResolvingHasFailed::fromException($job, $e));

            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error while resolving Job: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->eventDispatcher->dispatch(JobResolvingHasFailed::fromException($job, $e));

            return;
        }

        $this->eventDispatcher->dispatch(new JobResolvingHasCompleted($job));
    }

    /**
     * Finds the first handler which is able to resolve the job according to its type.
     *
     * @throws NoTaskResolvingHandlerFoundException if no resolver for the job's type was found
     */
    private function getTaskResolver(ResolverJobIdentifier $job): TaskResolverInterface
    {
        foreach ($this->taskResolvers as $resolver) {
            if ($resolver->canResolveTasks($job->type)) {
                return $resolver;
            }
        }

        throw new NoTaskResolvingHandlerFoundException($job->id, $job->type);
    }
}
