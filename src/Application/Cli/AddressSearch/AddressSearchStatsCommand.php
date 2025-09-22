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
final readonly class AddressSearchStatsCommand
{
    public function __construct(
        private BuildingAddressStatsProviderInterface $statsProvider,
    ) {}

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $list[] = ['Indexed addresses' => $this->statsProvider->countIndexedAddresses()];

        $io = new SymfonyStyle($input, $output);
        $io->definitionList(...$list);

        return Command::SUCCESS;
    }
}
