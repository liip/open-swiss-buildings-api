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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of items to show', self::DEFAULT_LIMIT)
            // Filters
            ->addOption('country-code', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries for the given country')
            ->addOption('building-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries with the building ID (EGID,GEID)')
            ->addOption('entrance-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries with the entrance ID (EDID)')
            ->addOption('language', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries in the given language, possible values: ' . $languages)
            ->addOption('canton', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries in the given canton')
            ->addOption('municipality', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries of the given municipality, case sensitive')
            ->addOption('street-name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries for the given street-name, case sensitive')
            ->addOption('street-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show only entries for the given street ID, if available')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit') ?? self::DEFAULT_LIMIT;
            $countryCode = OptionHelper::getStringBackedEnumListOptionValues($input, 'country-code', CountryCodeEnum::class);
            $buildingIds = OptionHelper::getStringListOptionValues($input, 'building-id');
            $entranceIds = OptionHelper::getStringListOptionValues($input, 'entrance-id');
            $cantons = OptionHelper::getStringListOptionValues($input, 'canton');
            $municipalities = OptionHelper::getStringListOptionValues($input, 'municipality');
            $streetNames = OptionHelper::getStringListOptionValues($input, 'street-name');
            $streetIds = OptionHelper::getStringListOptionValues($input, 'street-id');
            $languages = OptionHelper::getStringBackedEnumListOptionValues($input, 'language', LanguageEnum::class);
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
