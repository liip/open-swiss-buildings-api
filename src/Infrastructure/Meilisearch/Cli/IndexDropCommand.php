<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Cli;

use App\Infrastructure\Meilisearch\AddressIndexConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'meilisearch:index:drop',
    description: 'Remove the index, *ALL* data is lost with this operation',
)]
final class IndexDropCommand extends Command
{
    public function __construct(
        #[Autowire(value: '%kernel.environment%')]
        private readonly string $env,
        private readonly AddressIndexConfigurator $configurator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('dev' !== $this->env) {
            $output->writeln('<error>This command is enabled only in the DEV environment');

            return Command::INVALID;
        }

        $this->configurator->dropBuildingAddressIndex();

        return Command::SUCCESS;
    }
}
