<?php

declare(strict_types=1);

namespace App\Application\Cli\BuildingData;

use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Model\LanguageEnum;
use App\Infrastructure\Pagination;
use App\Infrastructure\Symfony\Console\OptionHelper;
use App\Infrastructure\Symfony\Console\Paginator;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:building-data:list',
    description: 'Shows the BuildingEntrances',
)]
final class BuildingDataListCommand extends Command
{
    private const int DEFAULT_LIMIT = 20;
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        private readonly BuildingEntranceReadRepositoryInterface $buildingEntranceRepository,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $languages  = implode('|', array_map(static fn(LanguageEnum $case) => $case->value, LanguageEnum::cases()));



    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Number of items to show')]
        int $limit = self::DEFAULT_LIMIT,
        // Filters
        #[Option(description: 'Show only entries for the given country', name: 'country-code')]
        ?array $countryCode = null,
        #[Option(description: 'Show only entries with the building ID (EGID,GEID)', name: 'building-id')]
        ?array $buildingId = null,
        #[Option(description: 'Show only entries with the entrance ID (EDID)', name: 'building-id')]
        ?array $entranceId = null,
        #[Option(description: 'Show only entries in the given language, possible values: de|fr|it|rm')]
        ?array $languages = null,
        #[Option(description: 'Show only entries in the given canton')]
        ?array $canton = null,
        #[Option(description: 'Show only entries of the given municipality, case sensitive')]
        ?string $municipality = null,
        #[Option(description: 'Show only entries for the given street-name, case sensitive', name: 'street-name')]
        ?string $streetName = null,
        #[Option(description: 'Show only entries for the given street ID, if available', name: 'street-id')]
        ?string $streetId = null,
    ): int {
        $io = new SymfonyStyle($input, $output);

        try {
            $countryCode = OptionHelper::getStringBackedEnumListOptionValues($countryCode, CountryCodeEnum::class);
            $buildingIds = OptionHelper::getStringListOptionValues($buildingId);
            $entranceIds = OptionHelper::getStringListOptionValues($entranceId);
            $cantons = OptionHelper::getStringListOptionValues($canton);
            $municipalities = OptionHelper::getStringListOptionValues($municipality);
            $streetNames = OptionHelper::getStringListOptionValues($streetName);
            $streetIds = OptionHelper::getStringListOptionValues($streetId);
            $languages = OptionHelper::getStringBackedEnumListOptionValues($languages, LanguageEnum::class);
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $pagination = new Pagination($limit);
        $filter = new BuildingEntranceFilter(
            countryCodes: $countryCode,
            cantonCodes: $cantons,
            buildingIds: $buildingIds,
            entranceIds: $entranceIds,
            municipalities: $municipalities,
            streetNames: $streetNames,
            streetIds: $streetIds,
            languages: $languages,
        );

        Paginator::paginate($io, $pagination, function (Pagination $pagination) use ($io, $filter): bool {
            $table = $io->createTable();
            $table->setHeaders([
                'ID',
                'Country',
                'Building ID',
                'Entrance ID',
                'Language',
                'Street ID',
                'Address',
                'Coordinates',
                'Imported at',
            ]);

            $count = 0;
            foreach ($this->buildingEntranceRepository->getPaginatedBuildingEntrances($pagination, $filter) as $buildingEntrance) {
                $table->addRow([
                    (string) $buildingEntrance->id,
                    $buildingEntrance->countryCode->value,
                    $buildingEntrance->buildingId,
                    $buildingEntrance->entranceId,
                    $buildingEntrance->streetNameLanguage->value,
                    $buildingEntrance->streetId,
                    $buildingEntrance->getAddress(),
                    json_encode($buildingEntrance->coordinates?->jsonSerialize()),
                    $this->formatImportedAt($buildingEntrance->importedAt),
                ]);
                ++$count;
            }

            $table->render();

            return $count === $pagination->limit;
        });

        return Command::SUCCESS;
    }

    private function formatImportedAt(\DateTimeImmutable $importedAt): string
    {
        $date = $importedAt->format(self::DATE_FORMAT);
        $diff = $this->clock->now()->diff($importedAt);

        if ($diff->days <= 0) {
            return $date;
        }

        return "{$date} ({$diff->days} days ago)";
    }
}
