<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:stats',
    description: 'Show stats from BuildingEntrance information as stored in the internal database',
)]
final class BuildingDataStatsCommand extends Command
{
    public function __construct(
        private readonly BuildingEntranceReadRepositoryInterface $buildingEntranceRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (CountryCodeEnum::cases() as $countryCode) {
            $stats = $this->buildingEntranceRepository->getStatistics($countryCode);

            $io->section("Stats for {$countryCode->value}, total: {$stats->total}");
            foreach ($stats->byCanton as $code => $count) {
                $io->writeln(" - <info>{$code}</info>: {$count}");
            }
        }

        return Command::SUCCESS;
    }
}
