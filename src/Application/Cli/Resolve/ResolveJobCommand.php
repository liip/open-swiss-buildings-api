<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\TaskResolvingHandlerInterface;
use App\Domain\Resolving\Event\ResolverAddressHasMatched;
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
    name: 'app:resolve:jobs:resolve',
    description: 'Resolves a single resolver job',
)]
final class ResolveJobCommand extends Command
{
    private ?ProgressIndicator $progress = null;

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
        private readonly TaskResolvingHandlerInterface $resolverHandler,
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

        if (!$job->isReadyForResolving()) {
            $io->error("The job {$job->id} is not ready for resolving, it's in state {$job->state->value}");

            return Command::FAILURE;
        }

        $this->progress = new ProgressIndicator($output);
        $this->progress->start("Resolving job {$job->id}");

        $this->resolverHandler->handleResolving($job->getIdentifier());

        return Command::SUCCESS;
    }

    #[AsEventListener]
    public function onTaskCreated(ResolverTaskHasBeenCreated $event): void
    {
        $this->progress?->advance();
    }

    #[AsEventListener]
    public function onAddressCreated(ResolverAddressHasMatched $event): void
    {
        $this->progress?->advance();
    }
}
