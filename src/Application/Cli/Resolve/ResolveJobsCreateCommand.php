<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Messaging\EventListener\ResolverJobMessageDispatcher;
use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:resolve:jobs:create',
    description: 'Create a resolver job with data coming from STDIN',
)]
final readonly class ResolveJobsCreateCommand
{
    public function __construct(
        private ResolverJobFactoryInterface $factory,
        private ResolverJobMessageDispatcher $dispatcher,
    ) {}

    /**
     * @param non-empty-string|null $charset
     * @param non-empty-string|null $delimiter
     * @param non-empty-string|null $enclosure
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Type of the resolver job, one of building_ids|municipalities_codes|geo_json|address_search')]
        string $type,
        #[Option(description: 'Specify to dispatch a message to the message bus for processing the job')]
        bool $dispatch = false,
        #[Option(description: 'Used charset in the specified CSV')]
        ?string $charset = null,
        #[Option(description: 'CSV delimiter to use', name: 'csv-delimiter')]
        ?string $delimiter = null,
        #[Option(description: 'CSV enclosure character to use', name: 'csv-enclosure')]
        ?string $enclosure = null,
    ): int {
        $dispatch = (bool) $input->getOption('dispatch');

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

        if (!$dispatch) {
            $this->dispatcher->preventNextMessage();
        }
        $job = $this->factory->create(ResolverTypeEnum::from($type), \STDIN, $metadata);

        $io = new SymfonyStyle($input, $output);
        $io->success("Created resolver job with ID {$job->id}");

        return Command::SUCCESS;
    }
}
