<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Data\ResolverJobRawDataRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:dump',
    description: 'Dump the raw data (CSV, GeoJson, ..) attached to a given Job to standard-output',
)]
final class ResolveJobsDumpCommand extends Command
{
    public function __construct(
        private readonly ResolverJobRawDataRepositoryInterface $jobReadRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'ID of the Job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = (string) $input->getArgument('jobId');

        try {
            $jobId = Uuid::fromString($jobId);
            $jobData = $this->jobReadRepository->getRawData($jobId);
        } catch (\Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $output->write(stream_get_contents($jobData->getResource()) ?: '-no-contents-');

        return Command::SUCCESS;
    }
}
