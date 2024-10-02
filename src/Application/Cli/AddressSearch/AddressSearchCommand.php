<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:search',
    description: 'Searches an address in the indexed data',
)]
final class AddressSearchCommand extends Command
{
    private const int DEFAULT_LIMIT = 3;

    public function __construct(
        private readonly BuildingAddressSearcherInterface $buildingAddressSearcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('search', InputArgument::REQUIRED, 'Address to search for')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of results to show', self::DEFAULT_LIMIT)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $search = $input->getArgument('search');
        $limit = OptionHelper::getPositiveIntOptionValue($input, 'limit', 1) ?? self::DEFAULT_LIMIT;

        $filter = new AddressSearch(
            limit: $limit,
            filterByQuery: $search,
        );

        $io = new SymfonyStyle($input, $output);

        $list = [];
        foreach ($this->buildingAddressSearcher->searchBuildingAddress($filter, true) as $result) {
            $matching = str_replace(['<em>', '</em>'], ['•<comment>', '</comment>•'], $result->matchingHighlight);
            $score = $result->score;

            if ([] !== $list) {
                $list[] = new TableSeparator();
            }
            $list[] = ['Match' => $matching];
            $list[] = ['ID' => $result->buildingAddress->id];
            $list[] = ['Building ID' => $result->buildingAddress->buildingId];
            $list[] = ['Score' => $score];
            if (null !== $result->rankingScoreDetails) {
                foreach ($result->rankingScoreDetails as $i => $details) {
                    $order = $i + 1;
                    $list[] = ["- #{$order}" => (string) $details];
                }
            }
        }

        $io->definitionList(...$list);

        return Command::SUCCESS;
    }
}
