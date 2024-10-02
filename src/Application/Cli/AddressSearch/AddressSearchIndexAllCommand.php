<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressIndexerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:index-all',
    description: 'Index ALL building addresses in the search index',
)]
final class AddressSearchIndexAllCommand extends Command
{
    public function __construct(
        private readonly BuildingAddressIndexerInterface $addressIndexer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progress = $io->createProgressBar();
        $progress->maxSecondsBetweenRedraws(2);
        $progress->minSecondsBetweenRedraws(1);

        $addressCount = $this->addressIndexer->countBuildingAddresses();

        $count = 0;
        foreach ($progress->iterate($this->addressIndexer->indexBuildingAddresses(), $addressCount) as $buildingAddress) {
            ++$count;
        }

        $io->success("Indexed {$count} building address");

        return Command::SUCCESS;
    }
}
