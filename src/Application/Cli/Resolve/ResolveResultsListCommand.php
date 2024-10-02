<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:results:list',
    description: 'Displays the results for a single resolver job',
)]
final class ResolveResultsListCommand extends Command
{
    public function __construct(
        private readonly ResolveResultsPrinter $printer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'ID of the resolver job');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of rows to load', ResolveResultsPrinter::DEFAULT_LIMIT);
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format of the output, one of ' . implode('|', ResolveResultsPrinter::FORMATS), ResolveResultsPrinter::DEFAULT_FORMAT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = $input->getArgument('jobId');
        if (!\is_string($jobId)) {
            throw new \LogicException('Argument jobId needs to be a string, but got ' . get_debug_type($jobId));
        }
        $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? ResolveResultsPrinter::DEFAULT_LIMIT;
        $format = $input->getOption('format') ?? ResolveResultsPrinter::DEFAULT_FORMAT;

        $io = new SymfonyStyle($input, $output);

        try {
            $jobId = Uuid::fromString($jobId);
            $this->printer->print($jobId, $limit, $format, $io);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
