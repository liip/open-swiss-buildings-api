<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:import',
    description: 'Imports the building data from the available building registries',
)]
final class BuildingDataImportCommand extends Command
{
    public function __construct(
        private readonly BuildingEntranceImporterInterface $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('country-code', null, InputOption::VALUE_REQUIRED, 'Only handle the given country')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $countryCode = OptionHelper::getStringBackedEnumOptionValue($input, 'country-code', CountryCodeEnum::class);

        $io = new SymfonyStyle($input, $output);
        $progress = $io->createProgressBar();
        $progress->maxSecondsBetweenRedraws(2);
        $progress->minSecondsBetweenRedraws(1);

        $buildingEntrancesCount = $this->importer->countBuildingEntrances($countryCode);

        $count = 0;
        foreach ($progress->iterate($this->importer->importBuildingData($countryCode), $buildingEntrancesCount) as $buildingData) {
            ++$count;
        }

        $io->success("Imported {$count} entries about building data");

        return Command::SUCCESS;
    }
}
