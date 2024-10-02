<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Messaging\EventListener\ResolverJobMessageDispatcher;
use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Symfony\Console\ArgumentHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:resolve:jobs:create',
    description: 'Create a resolver job with data coming from STDIN',
)]
final class ResolveJobsCreateCommand extends Command
{
    public function __construct(
        private readonly ResolverJobFactoryInterface $factory,
        private readonly ResolverJobMessageDispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $types = array_column(ResolverTypeEnum::cases(), 'value');

        $this->addArgument('type', InputArgument::REQUIRED, 'Type of the resolver job, one of ' . implode(',', $types))
            ->addOption('dispatch', null, InputOption::VALUE_NONE, 'Specify to dispatch a message to the message bus for processing the job')
            ->addOption('charset', null, InputOption::VALUE_REQUIRED, 'Used charset in the specified CSV')
            ->addOption('csv-delimiter', null, InputOption::VALUE_REQUIRED, 'CSV delimiter to use')
            ->addOption('csv-enclosure', null, InputOption::VALUE_REQUIRED, 'CSV enclosure character to use')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = ArgumentHelper::getStringBackedEnumArgument($input, 'type', ResolverTypeEnum::class);
        $dispatch = (bool) $input->getOption('dispatch');

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

        if (!$dispatch) {
            $this->dispatcher->preventNextMessage();
        }
        $job = $this->factory->create($type, \STDIN, $metadata);

        $io = new SymfonyStyle($input, $output);
        $io->success("Created resolver job with ID {$job->id}");

        return Command::SUCCESS;
    }
}
