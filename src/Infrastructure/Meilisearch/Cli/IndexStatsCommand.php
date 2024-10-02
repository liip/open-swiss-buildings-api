<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Cli;

use App\Infrastructure\Meilisearch\AddressIndexConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'meilisearch:index:stats',
    description: 'Display some stats from the index',
)]
final class IndexStatsCommand extends Command
{
    public function __construct(
        private readonly AddressIndexConfigurator $configurator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->configurator->getBuildingEntranceIndex();
        dump($index->getSettings());
        dump($index->getTasks());

        return Command::SUCCESS;
    }
}
