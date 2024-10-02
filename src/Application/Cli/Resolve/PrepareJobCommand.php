<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Messaging\EventListener\ResolverJobMessageDispatcher;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\JobPreparationHandlerInterface;
use App\Domain\Resolving\Event\ResolverAddressHasBeenCreated;
use App\Domain\Resolving\Event\ResolverTaskHasBeenCreated;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:prepare',
    description: 'Prepares a single resolver job',
)]
final class PrepareJobCommand extends Command
{
    private ?ProgressIndicator $progress = null;

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
        private readonly JobPreparationHandlerInterface $preparer,
        private readonly ResolverJobMessageDispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'ID of the resolver job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = $input->getArgument('jobId');
        if (!\is_string($jobId)) {
            throw new \LogicException('Argument jobId needs to be a string, but got ' . get_debug_type($jobId));
        }

        $io = new SymfonyStyle($input, $output);

        try {
            $jobId = Uuid::fromString($jobId);
            $job = $this->jobRepository->getJobInfo($jobId);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!$job->isReadyForPreparation()) {
            $io->error("The job {$job->id} is not ready for preparation, it's in state {$job->state->value}");

            return Command::FAILURE;
        }

        $this->progress = new ProgressIndicator($output);
        $this->progress->start("Preparing job {$job->id}");

        // Do not dispatch a message for further processing the job, because this command is used for doing it manually
        $this->dispatcher->preventNextMessage();
        $this->preparer->handlePreparation($job->getIdentifier());

        $job = $this->jobRepository->getJobInfo($jobId);
        if ($job->isFailed()) {
            $io->error("The job {$job->id} failed, {$job->failure}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    #[AsEventListener]
    public function onTaskCreated(ResolverTaskHasBeenCreated $event): void
    {
        $this->progress?->advance();
    }

    #[AsEventListener]
    public function onAddressCreated(ResolverAddressHasBeenCreated $event): void
    {
        $this->progress?->advance();
    }
}
