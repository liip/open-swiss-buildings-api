<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Contract\ResolverJobResolveMessageDispatcherInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:dispatch-resolve',
    description: 'Dispatch the message to trigger the "resolve" process for a given job',
)]
final class ResolveJobsDispatchResolveCommand extends Command
{
    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobFinder,
        private readonly ResolverJobResolveMessageDispatcherInterface $jobResolvingDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'ID of the Job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jobStringId = (string) $input->getArgument('jobId');

        try {
            $jobId = $this->jobFinder->getJobIdentifier(Uuid::fromString($jobStringId));
        } catch (ResolverJobNotFoundException) {
            $io->error('Could not find job for ID ' . $jobStringId);

            return Command::FAILURE;
        }

        $this->jobResolvingDispatcher->dispatchJobForResolving($jobId);

        $io->success("Queued resolving of Job with ID {$jobId}");

        return Command::SUCCESS;
    }
}
