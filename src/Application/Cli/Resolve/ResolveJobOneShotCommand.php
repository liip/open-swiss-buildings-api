<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Messaging\EventListener\ResolverJobMessageDispatcher;
use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Contract\JobPreparationHandlerInterface;
use App\Domain\Resolving\Contract\TaskResolvingHandlerInterface;
use App\Domain\Resolving\Event\ResolverAddressHasBeenCreated;
use App\Domain\Resolving\Event\ResolverTaskHasBeenCreated;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Symfony\Console\ArgumentHelper;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsCommand(
    name: 'app:resolve:jobs:one-shot',
    description: 'Create a resolver job with data coming from STDIN, prepare and resolve it, and output the results',
)]
final class ResolveJobOneShotCommand extends Command
{
    private ?ProgressIndicator $progress = null;

    public function __construct(
        private readonly ResolverJobFactoryInterface $factory,
        private readonly JobPreparationHandlerInterface $preparer,
        private readonly TaskResolvingHandlerInterface $resolverHandler,
        private readonly ResolverJobReadRepositoryInterface $jobReadRepository,
        private readonly ResolverJobWriteRepositoryInterface $jobWriteRepository,
        private readonly ResolveResultsPrinter $printer,
        private readonly ResolverJobMessageDispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $types = array_column(ResolverTypeEnum::cases(), 'value');

        $this->addArgument('type', InputArgument::REQUIRED, 'Type of the resolver job, one of ' . implode(',', $types))
            ->addOption('charset', null, InputOption::VALUE_REQUIRED, 'Used charset in the specified CSV')
            ->addOption('csv-delimiter', null, InputOption::VALUE_REQUIRED, 'CSV delimiter to use')
            ->addOption('csv-enclosure', null, InputOption::VALUE_REQUIRED, 'CSV enclosure character to use')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Specify to enable debug output of the first result, instead of listing the results')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of rows to load', ResolveResultsPrinter::DEFAULT_LIMIT)
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format of the output, one of ' . implode('|', ResolveResultsPrinter::FORMATS), ResolveResultsPrinter::DEFAULT_FORMAT)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? ResolveResultsPrinter::DEFAULT_LIMIT;
        $format = $input->getOption('format') ?? ResolveResultsPrinter::DEFAULT_FORMAT;

        $job = $this->createJob($input);
        $io->success("Created resolver job with ID {$job->id}");

        try {
            $this->prepareJob($job, $output);
            $jobInfo = $this->jobReadRepository->getJobInfo($job->id);
            if (ResolverJobStateEnum::FAILED === $jobInfo->state && null !== $jobInfo->failure) {
                $io->writeln('');
                $io->error($jobInfo->failure->details);

                return Command::FAILURE;
            }
            $this->resolveJob($job, $output);
            $io->writeln('');
            if ($input->getOption('debug')) {
                $this->printer->printDebug($job->id, 1, $io);
            } else {
                $this->printer->print($job->id, $limit, $format, $io);
            }
        } finally {
            $this->jobWriteRepository->delete($job->id);
            $io->success("Deleted resolver job with ID {$job->id}");
        }

        return Command::SUCCESS;
    }

    private function createJob(InputInterface $input): ResolverJobIdentifier
    {
        $type = ArgumentHelper::getStringBackedEnumArgument($input, 'type', ResolverTypeEnum::class);

        $metadata = new ResolverMetadata();
        if (null !== ($charset = $input->getOption('charset'))) {
            $metadata = $metadata->withCharset($charset);
        }
        if (null !== ($delimiter = $input->getOption('csv-delimiter'))) {
            $metadata = $metadata->withCsvDelimiter($delimiter);
        }
        if (null !== ($enclosure = $input->getOption('csv-enclosure'))) {
            $metadata = $metadata->withCsvEnclosure($enclosure);
        }

        // Do not dispatch a message for further processing the job, because this command is used for doing it manually
        $this->dispatcher->preventNextMessage();

        return $this->factory->create($type, \STDIN, $metadata);
    }

    private function prepareJob(ResolverJobIdentifier $job, OutputInterface $output): void
    {
        $this->progress = new ProgressIndicator($output);
        $this->progress->start("Preparing job {$job->id}");

        // Do not dispatch a message for further processing the job, because this command is used for doing it manually
        $this->dispatcher->preventNextMessage();
        $this->preparer->handlePreparation($job);
    }

    private function resolveJob(ResolverJobIdentifier $job, OutputInterface $output): void
    {
        $this->progress = new ProgressIndicator($output);
        $this->progress->start("Preparing job {$job->id}");

        $this->resolverHandler->handleResolving($job);
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
