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
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:registry:li:list',
    description: 'Show the Liechtenstein data related to the entrances',
)]
final readonly class RegistryLiListCommand
{
    private const int DEFAULT_LIMIT = 30;

    public function __construct(
        #[Autowire(service: RegistryBuildingDataRepository::class)]
        private RegistryBuildingDataRepositoryInterface $repository,
    ) {}

    /**
     * @param string[]|null $buildingId
     * @param string[]|null $entranceId
     * @param string[]|null $municipalityName
     * @param string[]|null $streetName
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Number of rows to load')]
        int $limit = self::DEFAULT_LIMIT,
        // Filters
        #[Option(description: 'Filter by building ID (GEID)', name: 'building-id')]
        ?array $buildingId = null,
        #[Option(description: 'Filter by entrance ID (GEDID)', name: 'entrance-id')]
        ?array $entranceId = null,
        #[Option(description: 'Filter by municipality name, case sensitive (GDENAME)', name: 'municipality-name')]
        ?array $municipalityName = null,
        #[Option(description: 'Filter by street name, case sensitive (STRNAME)', name: 'street-name')]
        ?array $streetName = null,
    ): int {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1');
        }
        $io = new SymfonyStyle($input, $output);

        try {
            $buildingIds = OptionHelper::getStringListOptionValues($buildingId);
            $entranceIds = OptionHelper::getStringListOptionValues($entranceId);
            $municipalityNames = OptionHelper::getStringListOptionValues($municipalityName);
            $streetNames = OptionHelper::getStringListOptionValues($streetName);
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
