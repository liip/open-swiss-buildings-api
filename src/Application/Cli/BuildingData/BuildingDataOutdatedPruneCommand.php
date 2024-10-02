<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:outdated:prune',
    description: 'Prunes the data imported by removing BuildingEntrances not updated anymore',
)]
final class BuildingDataOutdatedPruneCommand extends Command
{
    private const int DEFAULT_ACTIVE_DAYS_WINDOW = 90;

    public function __construct(
        private readonly BuildingEntranceReadRepositoryInterface $buildingEntranceReadRepository,
        private readonly BuildingEntranceWriteRepositoryInterface $buildingEntranceWriteRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('active-days', null, InputOption::VALUE_REQUIRED, 'Number of days in the past to consider an imported item as active', self::DEFAULT_ACTIVE_DAYS_WINDOW);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeDays = OptionHelper::getPositiveIntOptionValue($input, 'active-days') ?? self::DEFAULT_ACTIVE_DAYS_WINDOW;

        $io = new SymfonyStyle($input, $output);

        $count = $this->buildingEntranceReadRepository->countOutdatedBuildingEntrances($activeDays);

        if ($count > 0) {
            $io->note("Found {$count} building data entries to cleanup");
            if ($io->confirm('Do you really want to delete them?')) {
                $count = $this->buildingEntranceWriteRepository->deleteOutdatedBuildingEntrances($activeDays);
                $io->success("Deleted {$count} outdated building data entries");
            }
        } else {
            $io->note('Found no building data entries to cleanup');
        }

        return Command::SUCCESS;
    }
}
