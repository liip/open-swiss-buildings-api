<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressIndexerInterface;
use App\Infrastructure\Model\CountryCodeEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:index-all',
    description: 'Index ALL building addresses in the search index',
)]
final readonly class AddressSearchIndexAllCommand
{
    public function __construct(
        private BuildingAddressIndexerInterface $addressIndexer,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Index only entries with the given country code', name: 'country-code')]
        ?string $countryCodeText = null,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $countryCode = $countryCodeText ? CountryCodeEnum::from($countryCodeText) : null;

        $progress = $io->createProgressBar();
        $progress->maxSecondsBetweenRedraws(2);
        $progress->minSecondsBetweenRedraws(1);

        $addressCount = $this->addressIndexer->count($countryCode);

        $count = 0;
        foreach ($progress->iterate($this->addressIndexer->indexAll($countryCode), $addressCount) as $buildingAddress) {
            ++$count;
        }

        $io->success("Indexed {$count} building address");

        return Command::SUCCESS;
    }
}
