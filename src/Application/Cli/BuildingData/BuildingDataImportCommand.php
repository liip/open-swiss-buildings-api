<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:import',
    description: 'Imports the building data from the federal archive',
)]
final class BuildingDataImportCommand extends Command
{
    public function __construct(
        private readonly BuildingEntranceImporterInterface $importer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progress = $io->createProgressBar();
        $progress->maxSecondsBetweenRedraws(2);
        $progress->minSecondsBetweenRedraws(1);

        $buildingEntrancesCount = $this->importer->countBuildingEntrances();

        $count = 0;
        foreach ($progress->iterate($this->importer->importBuildingData(), $buildingEntrancesCount) as $buildingData) {
            ++$count;
        }

        $io->success("Imported {$count} entries about building data");

        return Command::SUCCESS;
    }
}
