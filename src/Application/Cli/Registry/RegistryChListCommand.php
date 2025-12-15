<?php

declare(strict_types=1);

namespace App\Application\Cli\Registry;

use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryInterface;
use App\Domain\Registry\DataCH\RegistryBuildingDataRepository;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Domain\Registry\Model\BuildingStatusEnum;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;
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
    name: 'app:registry:ch:list',
    description: 'Show the Swiss Federal-Data related to the entrances',
)]
final readonly class RegistryChListCommand
{
    private const int DEFAULT_LIMIT = 30;

    public function __construct(
        #[Autowire(service: RegistryBuildingDataRepository::class)]
        private RegistryBuildingDataRepositoryInterface $repository,
    ) {}

    /**
     * @param string[]|null $buildingId
     * @param string[]|null $entranceId
     * @param string[]|null $language
     * @param string[]|null $municipalityName
     * @param string[]|null $streetName
     * @param string[]|null $streetId
     * @param string[]|null $canton
     * @param string[]|null $buildingStatus
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Number of rows to load')]
        int $limit = self::DEFAULT_LIMIT,
        // Filters
        #[Option(description: 'Filter by building ID (EGID)', name: 'building-id')]
        ?array $buildingId = null,
        #[Option(description: 'Filter by entrance ID (EDID)', name: 'entrance-id')]
        ?array $entranceId = null,
        #[Option(description: 'Filter by language (9901|9902|9903|9904)')]
        ?array $language = null,
        #[Option(description: 'Filter by municipality name, case sensitive (GGDENAME)', name: 'municipality-name')]
        ?array $municipalityName = null,
        #[Option(description: 'Filter by street name, case sensitive (STRNAME)', name: 'street-name')]
        ?array $streetName = null,
        #[Option(description: 'Filter by street ID (ESID)', name: 'street-id')]
        ?array $streetId = null,
        #[Option(description: 'Filter by canton code (GDEKR)')]
        ?array $canton = null,
        #[Option(description: 'Filter by building status code (GSTAT)', name: 'building-status')]
        ?array $buildingStatus = null,
    ): int {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1');
        }
        $io = new SymfonyStyle($input, $output);

        try {
            $buildingIds = OptionHelper::getStringListOptionValues($buildingId);
            $entranceIds = OptionHelper::getStringListOptionValues($entranceId);
            $languages = OptionHelper::getStringBackedEnumListOptionValues($language, LanguageEnum::class);
            $cantons = OptionHelper::getStringListOptionValues($canton);
            $municipalityNames = OptionHelper::getStringListOptionValues($municipalityName);
            $streetNames = OptionHelper::getStringListOptionValues($streetName);
            $streetIds = OptionHelper::getStringListOptionValues($streetId);
            $buildingStatuses = OptionHelper::getStringBackedEnumListOptionValues($buildingStatus, BuildingStatusEnum::class);
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $pagination = new Pagination($limit);
        $filter = new BuildingEntranceFilter(
            cantonCodes: $cantons,
            buildingIds: $buildingIds,
            entranceIds: $entranceIds,
            municipalityNames: $municipalityNames,
            streetNames: $streetNames,
            streetIds: $streetIds,
            languages: $languages,
            buildingStatuses: $buildingStatuses,
            countryCode: [CountryCodeEnum::CH],
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
                'GeoCoords <East,North>',
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
