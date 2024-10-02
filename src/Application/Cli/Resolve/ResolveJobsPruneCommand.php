<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:resolve:jobs:prune',
    description: 'Deletes expired resolver jobs',
)]
final class ResolveJobsPruneCommand extends Command
{
    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $readRepository,
        private readonly ResolverJobWriteRepositoryInterface $writeRepository,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->readRepository->getExpiredJobs($this->clock->now()) as $job) {
            if ($io->confirm("Delete the job {$job}?", true)) {
                $this->writeRepository->delete($job->id);
            }
        }

        return Command::SUCCESS;
    }
}
