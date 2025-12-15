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
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsCommand(
    name: 'app:resolve:jobs:one-shot',
    description: 'Create a resolver job with data coming from STDIN, prepare and resolve it, and output the results',
)]
final class ResolveJobOneShotCommand
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
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Type of the resolver job, one of building_ids|municipalities_codes|geo_json|address_search')]
        string $type,
        #[Option(description: 'Used charset in the specified CSV')]
        ?string $charset = null,
        #[Option(description: 'CSV delimiter to use', name: 'csv-delimiter')]
        ?string $csvDelimiter = null,
        #[Option(description: 'CSV enclosure character to use', name: 'csv-enclosure')]
        ?string $csvEnclosure = null,
        #[Option(description: 'Specify to enable debug output of the first result, instead of listing the results')]
        bool $debug = false,
        #[Option(description: 'Number of rows to load')]
        int $limit = ResolveResultsPrinter::DEFAULT_LIMIT,
        #[Option(description: 'Format of the output, one of table|csv|csv-file|json-file|none')]
        string $format = ResolveResultsPrinter::DEFAULT_FORMAT,
    ): int {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1');
        }
        if (\in_array('', [$charset, $csvDelimiter, $csvEnclosure], true)) {
            throw new \InvalidArgumentException('Charset, delimiter and enclosure must be not set or non-empty string');
        }
        if (!\in_array($format, ResolveResultsPrinter::FORMATS, true)) {
            throw new \InvalidArgumentException("Unknown format {$format}, supported formats are " . implode(', ', ResolveResultsPrinter::FORMATS));
        }

        $io = new SymfonyStyle($input, $output);

        $job = $this->createJob(ResolverTypeEnum::from($type), $charset, $csvDelimiter, $csvEnclosure);
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
            if ($debug) {
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

    /**
     * @param non-empty-string|null $charset
     * @param non-empty-string|null $delimiter
     * @param non-empty-string|null $enclosure
     */
    private function createJob(ResolverTypeEnum $type, ?string $charset, ?string $delimiter, ?string $enclosure): ResolverJobIdentifier
    {
        $metadata = new ResolverMetadata();
        if (null !== $charset) {
            $metadata = $metadata->withCharset($charset);
        }
        if (null !== $delimiter) {
            $metadata = $metadata->withCsvDelimiter($delimiter);
        }
        if (null !== $enclosure) {
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

    #[AsEventListener(event: ResolverTaskHasBeenCreated::class)]
    public function onTaskCreated(): void
    {
        $this->progress?->advance();
    }

    #[AsEventListener(event: ResolverAddressHasBeenCreated::class)]
    public function onAddressCreated(): void
    {
        $this->progress?->advance();
    }
}
