<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:address-search:search',
    description: 'Searches an address in the indexed data',
)]
final readonly class AddressSearchCommand
{
    private const int DEFAULT_LIMIT = 3;

    public function __construct(
        private BuildingAddressSearcherInterface $buildingAddressSearcher,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Address to search for')]
        string $search,
        #[Option(description: 'Number of results to show')]
        int $limit = self::DEFAULT_LIMIT,
    ): int {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit can not be less than 1');
        }
        $search = '' === $search ? null : $search;
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
