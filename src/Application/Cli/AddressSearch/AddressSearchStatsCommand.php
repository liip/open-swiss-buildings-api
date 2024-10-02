<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressStatsProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:stats',
    description: 'Provides stats for the indexed data',
)]
final class AddressSearchStatsCommand extends Command
{
    public function __construct(
        private readonly BuildingAddressStatsProviderInterface $statsProvider,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $list[] = ['Indexed addresses' => $this->statsProvider->countIndexedAddresses()];

        $io = new SymfonyStyle($input, $output);
        $io->definitionList(...$list);

        return Command::SUCCESS;
    }
}
