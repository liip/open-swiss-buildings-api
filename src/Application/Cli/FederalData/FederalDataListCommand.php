<?php

declare(strict_types=1);

namespace App\Application\Cli\FederalData;

use App\Domain\FederalData\Contract\FederalBuildingDataRepositoryInterface;
use App\Domain\FederalData\Model\BuildingStatusEnum;
use App\Domain\FederalData\Model\EntranceLanguageEnum;
use App\Domain\FederalData\Model\FederalEntranceFilter;
use App\Infrastructure\Pagination;
use App\Infrastructure\Symfony\Console\OptionHelper;
use App\Infrastructure\Symfony\Console\Paginator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:federal-data:list',
    description: 'Show the Federal-Data related to the entrances',
)]
final class FederalDataListCommand extends Command
{
    private const int DEFAULT_LIMIT = 30;

    public function __construct(
        private readonly FederalBuildingDataRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of rows to load', self::DEFAULT_LIMIT)
            // Filters
            ->addOption('building-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by building ID (EGID)')
            ->addOption('entrance-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by entrance ID (EDID)')
            ->addOption('language', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by language (9901|9902|9903|9904)')
            ->addOption('municipality', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by municipality, case sensitive (GGDENAME)')
            ->addOption('street-name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by street name, case sensitive (STRNAME)')
            ->addOption('street-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by street ID (ESID)')
            ->addOption('canton', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by canton code (GDEKR)')
            ->addOption('building-status', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by building status code (GSTAT)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? self::DEFAULT_LIMIT;
            $buildingIds = OptionHelper::getStringListOptionValues($input, 'building-id');
            $entranceIds = OptionHelper::getStringListOptionValues($input, 'entrance-id');
            $languages = OptionHelper::getStringBackedEnumListOptionValues($input, 'language', EntranceLanguageEnum::class);
            $cantons = OptionHelper::getStringListOptionValues($input, 'canton');
            $municipalities = OptionHelper::getStringListOptionValues($input, 'municipality');
            $streetNames = OptionHelper::getStringListOptionValues($input, 'street-name');
            $streetIds = OptionHelper::getStringListOptionValues($input, 'street-id');
            $buildingStatuses = OptionHelper::getStringBackedEnumListOptionValues($input, 'building-status', BuildingStatusEnum::class);
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $pagination = new Pagination($limit);
        $filter = new FederalEntranceFilter(
            cantonCodes: $cantons,
            buildingIds: $buildingIds,
            entranceIds: $entranceIds,
            municipalities: $municipalities,
            streetNames: $streetNames,
            streetIds: $streetIds,
            languages: $languages,
            buildingStatuses: $buildingStatuses,
        );

        Paginator::paginate($io, $pagination, function (Pagination $pagination) use ($io, $filter): bool {
            $table = $io->createTable();
            $table->setHeaders([
                'Building ID (EGID)',
                'Entrance ID (EDID)',
                'Status (GSTAT)',
                'Language (STRSP)',
                'Canton (GDEKT)',
                'Postal Code (DPLZ4)',
                'Municipality (GGDENAME)',
                'Locality (DPLZNAME)',
                'Street (STRNAME, DEINR, ESID)',
                'GeoCoords <East,Nord>',
            ]);

            $count = 0;
            foreach ($this->repository->getPaginatedBuildingData($pagination, $filter) as $entrance) {
                $table->addRow([
                    $entrance->buildingId,
                    $entrance->entranceId,
                    $entrance->buildingStatus->value,
                    $entrance->streetNameLanguage->value,
                    $entrance->cantonCode,
                    $entrance->postalCode,
                    $entrance->municipality,
                    $entrance->locality,
                    "{$entrance->streetName} {$entrance->streetHouseNumber} ({$entrance->streetId})",
                    "<{$entrance->geoCoordinateEastLV95},{$entrance->geoCoordinateNorthLV95}>",
                ]);
                ++$count;
            }

            $table->render();

            return $count === $pagination->limit;
        });

        return Command::SUCCESS;
    }
}
