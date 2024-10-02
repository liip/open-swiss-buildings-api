<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Cli;

use App\Infrastructure\Meilisearch\AddressIndexConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'meilisearch:address:get',
    description: 'Outputs the data as stored in the index',
)]
final class AddressGetCommand extends Command
{
    public function __construct(
        private readonly AddressIndexConfigurator $configurator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->configurator->getBuildingEntranceIndex();
        $id = $input->getArgument('id');

        $record = $index->getDocument($id);
        dump($record);

        return Command::SUCCESS;
    }
}
