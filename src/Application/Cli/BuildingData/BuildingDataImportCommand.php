<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceImporterInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Doctrine\DBAL\Exception\TableNotFoundException;
use League\Csv\UnavailableStream;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:import',
    description: 'Imports the building data from the available building registries',
)]
final readonly class BuildingDataImportCommand
{
    public function __construct(
        private BuildingEntranceImporterInterface $importer,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Only handle the given country', name: 'country-code')]
        ?string $countryCode = null,
    ): int {
        $countryCode = $countryCode ? CountryCodeEnum::from($countryCode) : null;

        $io = new SymfonyStyle($input, $output);
        $progress = $io->createProgressBar();
        $progress->maxSecondsBetweenRedraws(2);
        $progress->minSecondsBetweenRedraws(1);

        try {
            $buildingEntrancesCount = $this->importer->countBuildingEntrances($countryCode);
        } catch (TableNotFoundException) {
            $io->error('Table not found - did you download the data with app:registry:ch:download?');

            return 1;
        } catch (UnavailableStream $e) {
            $io->error("CSV file not found - did you download the data with app:registry:li:download?\n\n{$e->getMessage()}");

            return 1;
        }

        $count = 0;
        foreach ($progress->iterate($this->importer->importBuildingData($countryCode), $buildingEntrancesCount) as $ignored) {
            ++$count;
        }

        $io->success("Imported {$count} entries about building data");

        return Command::SUCCESS;
    }
}
