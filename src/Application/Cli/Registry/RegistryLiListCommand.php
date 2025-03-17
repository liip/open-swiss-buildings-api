<?php

declare(strict_types=1);

namespace App\Application\Cli\Registry;

use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryInterface;
use App\Domain\Registry\DataLI\RegistryBuildingDataRepository;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use App\Infrastructure\Symfony\Console\OptionHelper;
use App\Infrastructure\Symfony\Console\Paginator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:registry:li:list',
    description: 'Show the Liechtenstein data related to the entrances',
)]
final class RegistryLiListCommand extends Command
{
    private const int DEFAULT_LIMIT = 30;

    public function __construct(
        #[Autowire(service: RegistryBuildingDataRepository::class)]
        private readonly RegistryBuildingDataRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of rows to load', self::DEFAULT_LIMIT)
            // Filters
            ->addOption('building-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by building ID (GEID)')
            ->addOption('entrance-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by entrance ID (GEDID)')
            ->addOption('municipality-name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by municipality name, case sensitive (GDENAME)')
            ->addOption('street-name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by street name, case sensitive (STRNAME)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? self::DEFAULT_LIMIT;
            $buildingIds = OptionHelper::getStringListOptionValues($input, 'building-id');
            $entranceIds = OptionHelper::getStringListOptionValues($input, 'entrance-id');
            $municipalityNames = OptionHelper::getStringListOptionValues($input, 'municipality-name');
            $streetNames = OptionHelper::getStringListOptionValues($input, 'street-name');
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $pagination = new Pagination($limit);
        $filter = new BuildingEntranceFilter(
            buildingIds: $buildingIds,
            entranceIds: $entranceIds,
            municipalityNames: $municipalityNames,
            streetNames: $streetNames,
            countryCode: [CountryCodeEnum::LI],
        );

        Paginator::paginate($io, $pagination, function (Pagination $pagination) use ($io, $filter): bool {
            $table = $io->createTable();
            $table->setHeaders([
                'Building ID',
                'Entrance ID',
                'Language',
                'Postal Code',
                'Municipality',
                'Locality',
                'Street',
                'GeoCoords <East,North>',
            ]);

            $count = 0;
            foreach ($this->repository->getPaginatedBuildingData($pagination, $filter) as $entrance) {
                $table->addRow([
                    $entrance->buildingId,
                    $entrance->entranceId,
                    $entrance->streetNameLanguage->value,
                    $entrance->postalCode,
                    $entrance->municipality,
                    $entrance->locality,
                    "{$entrance->streetName} {$entrance->streetHouseNumber}",
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
