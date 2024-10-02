<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Cli;

use App\Infrastructure\Meilisearch\AddressIndexConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'meilisearch:index:reconfigure',
    description: 'Reconfigure the index, no data is lost with this operation',
)]
final class IndexReconfigureCommand extends Command
{
    public function __construct(
        private readonly AddressIndexConfigurator $configurator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configurator->configureBuildingAddressIndex();

        return Command::SUCCESS;
    }
}
