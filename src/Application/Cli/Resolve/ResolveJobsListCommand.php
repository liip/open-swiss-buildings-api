<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:resolve:jobs:list',
    description: 'Displays a list of current resolver jobs',
)]
final class ResolveJobsListCommand extends Command
{
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('with-metadata', '', InputOption::VALUE_NONE, 'Include the metadata of the jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $withMetadata = (bool) $input->getOption('with-metadata');

        $table = $io->createTable();
        $headers = ['ID', 'Type', 'State', 'Created at', 'Modified at', 'Expires at', 'Failure'];
        if ($withMetadata) {
            $headers[] = 'Metadata';
        }
        $table->setHeaders($headers);

        foreach ($this->repository->getJobs() as $job) {
            $row = [
                $job->id,
                $job->type->value,
                $job->state->value,
                $job->createdAt->format(self::DATE_FORMAT),
                $job->modifiedAt->format(self::DATE_FORMAT),
                $job->expiresAt->format(self::DATE_FORMAT),
                $job->failure ? json_encode($job->failure, \JSON_THROW_ON_ERROR) : '',
            ];

            if ($withMetadata) {
                $row[] = json_encode($job->metadata, \JSON_THROW_ON_ERROR);
            }

            $table->addRow($row);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
