<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:outdated:list',
    description: 'Shows the outdated BuildingEntrances, that are not updated anymore',
)]
final readonly class BuildingDataOutdatedListCommand
{
    private const int DEFAULT_ACTIVE_DAYS_WINDOW = 90;

    private const string DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private BuildingEntranceReadRepositoryInterface $buildingEntranceRepository,
        private ClockInterface $clock,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Number of days in the past to consider an imported item as active', name: 'active-days')]
        int $activeDays = self::DEFAULT_ACTIVE_DAYS_WINDOW,
    ): int {
        if ($activeDays < 1) {
            throw new \InvalidArgumentException('Active days needs to be at least 1');
        }
        $io = new SymfonyStyle($input, $output);

        $table = $io->createTable();
        $table->setHeaders([
            'ID',
            'Building ID',
            'Entrance ID',
            'Address',
            'Imported at',
        ]);

        foreach ($this->buildingEntranceRepository->getOutdatedBuildingEntrances($activeDays) as $buildingEntrance) {
            $table->addRow([
                (string) $buildingEntrance->id,
                $buildingEntrance->buildingId,
                $buildingEntrance->entranceId,
                $buildingEntrance->getAddress(),
                $this->formatImportedAt($buildingEntrance->importedAt),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function formatImportedAt(\DateTimeImmutable $importedAt): string
    {
        $date = $importedAt->format(self::DATE_FORMAT);
        $diff = $this->clock->now()->diff($importedAt);

        return "{$date} ({$diff->days} days ago)";
    }
}
