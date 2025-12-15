<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Data\ResolverJobRawDataRepositoryInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:dump',
    description: 'Dump the raw data (CSV, GeoJson, ..) attached to a given Job to standard-output',
)]
final readonly class ResolveJobsDumpCommand
{
    public function __construct(
        private ResolverJobRawDataRepositoryInterface $jobReadRepository,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'ID of the Job', name: 'jobId')]
        string $jobId,
    ): int {
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
