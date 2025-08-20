<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:list',
    description: 'List the addresses in the indexed data',
)]
final readonly class AddressSearchListCommand
{
    private const int DEFAULT_LIMIT = 10;

    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private BuildingAddressSearcherInterface $buildingAddressSearcher,
    ) {}

    /**
     * @param ?string[] $id
     * @param ?string[] $buildingIds
     * @param ?string[] $countryCodes
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Number of items to show')]
        int $limit = self::DEFAULT_LIMIT,
        // Filters
        #[Option(description: 'Show only entries with the ID')]
        ?array $id = null,
        #[Option(description: 'Show only entries with the given building ID (EGID, GEID)', name: 'building-id')]
        ?array $buildingIds = null,
        #[Option(description: 'Show only entries with the given country code', name: 'country-code')]
        ?array $countryCodes = null,
    ): int {
        $io = new SymfonyStyle($input, $output);

        try {
            if ($limit < 1) {
                throw new \UnexpectedValueException('Limit must be at least 1');
            }
            $ids = OptionHelper::getStringListOptionValues($id);
            $buildingIds = OptionHelper::getStringListOptionValues($buildingIds);
            $countryCodes = OptionHelper::getStringBackedEnumListOptionValues($countryCodes, CountryCodeEnum::class);
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $filter = new AddressSearch(
            limit: $limit,
            filterByIds: $ids,
            filterByBuildingIds: $buildingIds,
            filterByCountryCodes: $countryCodes,
        );

        $table = $io->createTable();
        $table->setHeaders([
            'ID',
            'Country',
            'Building ID',
            'Entrance ID',
            'Language',
            'Street ID',
            'Address',
            'Coords',
            'Imported at',
        ]);

        foreach ($this->buildingAddressSearcher->searchBuildingAddress($filter) as $result) {
            $updatedAt = \DateTimeImmutable::createFromFormat('Ymd', (string) $result->buildingAddress->importedAt) ?: null;

            $table->addRow([
                $result->buildingAddress->id,
                $result->buildingAddress->address->countryCode,
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
