<?php

declare(strict_types=1);

namespace App\Domain\Resolving;

use App\Domain\Resolving\Contract\Data\ResolverJobRawDataRepositoryInterface;
use App\Domain\Resolving\Contract\Job\JobPreparerInterface;
use App\Domain\Resolving\Contract\JobPreparationHandlerInterface;
use App\Domain\Resolving\Event\JobPreparationHasCompleted;
use App\Domain\Resolving\Event\JobPreparationHasStarted;
use App\Domain\Resolving\Event\JobResolvingHasFailed;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Exception\NoJobPreparationHandlerFoundException;
use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Failure\ResolverJobFailureEnum;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class JobPreparationHandler implements JobPreparationHandlerInterface
{
    public function __construct(
        private ResolverJobRawDataRepositoryInterface $jobRepository,
        /**
         * @var list<JobPreparerInterface>
         */
        #[AutowireIterator(JobPreparerInterface::class)]
        private iterable $preparers,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handlePreparation(ResolverJobIdentifier $job): void
    {
        $this->eventDispatcher->dispatch(new JobPreparationHasStarted($job));

        $data = $this->jobRepository->getRawData($job->id);

        try {
            $this->getPreparer($job)->prepareJob($data);
        } catch (NoJobPreparationHandlerFoundException $e) {
            $this->eventDispatcher->dispatch(new JobResolvingHasFailed(
                $job,
                ResolverJobFailure::fromException(ResolverJobFailureEnum::NO_JOB_PREPARER, $e),
            ));

            return;
        } catch (InvalidInputDataException $e) {
            $this->eventDispatcher->dispatch(new JobResolvingHasFailed(
                $job,
                ResolverJobFailure::fromException(ResolverJobFailureEnum::INVALID_DATA, $e),
            ));

            return;
        }

        $this->eventDispatcher->dispatch(new JobPreparationHasCompleted($job));
    }

    /**
     * Finds the first preparer which is able to prepare the job according to its type.
     *
     * @throws NoJobPreparationHandlerFoundException if no handler for the job's type was found
     */
    private function getPreparer(ResolverJobIdentifier $job): JobPreparerInterface
    {
        foreach ($this->preparers as $preparer) {
            if ($preparer->canPrepareJob($job->type)) {
                return $preparer;
            }
        }

        throw new NoJobPreparationHandlerFoundException($job->id, $job->type);
    }
}
