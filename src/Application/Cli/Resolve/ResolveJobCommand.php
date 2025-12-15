<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\TaskResolvingHandlerInterface;
use App\Domain\Resolving\Event\ResolverAddressHasMatched;
use App\Domain\Resolving\Event\ResolverTaskHasBeenCreated;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:resolve',
    description: 'Resolves a single resolver job',
)]
final class ResolveJobCommand
{
    private ?ProgressIndicator $progress = null;

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
        private readonly TaskResolvingHandlerInterface $resolverHandler,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'ID of the resolver job', name: 'jobId')]
        string $jobId,
    ): int {
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

    #[AsEventListener(event: ResolverTaskHasBeenCreated::class)]
    public function onTaskCreated(): void
    {
        $this->progress?->advance();
    }

    #[AsEventListener(event: ResolverAddressHasMatched::class)]
    public function onAddressCreated(): void
    {
        $this->progress?->advance();
    }
}
