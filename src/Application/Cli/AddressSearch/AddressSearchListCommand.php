<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:list',
    description: 'List the addresses in the indexed data',
)]
final class AddressSearchListCommand extends Command
{
    private const int DEFAULT_LIMIT = 3;
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly BuildingAddressSearcherInterface $buildingAddressSearcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of items to show', self::DEFAULT_LIMIT)
            // Filters
            ->addOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries with the ID)')
            ->addOption('building-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries with the building ID (EGID)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? self::DEFAULT_LIMIT;
            $ids = OptionHelper::getStringListOptionValues($input, 'id');
            $buildingIds = OptionHelper::getStringListOptionValues($input, 'building-id');
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $filter = new AddressSearch(
            limit: $limit,
            filterByIds: $ids,
            filterByBuildingIds: $buildingIds,
        );

        $table = $io->createTable();
        $table->setHeaders([
            'ID',
            'Building ID',
            'Entrance ID',
            'Language',
            'Street ID',
            'Address',
            'Coords',
            'Imported at',
        ]);

        foreach ($this->buildingAddressSearcher->searchBuildingAddress($filter) as $result) {
            $updatedAt = \DateTimeImmutable::createFromFormat('U', (string) $result->buildingAddress->importedAtTimestamp) ?: null;

            $table->addRow([
                $result->buildingAddress->id,
                $result->buildingAddress->buildingId,
                $result->buildingAddress->entranceId,
                $result->buildingAddress->language,
                $result->buildingAddress->streetId,
                $result->buildingAddress->address->formatForSearch(),
                json_encode($result->buildingAddress->coordinates?->jsonSerialize()),
                $updatedAt?->format(self::DATE_FORMAT),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
