<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Infrastructure\Symfony\Console\Autocomplete;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:address-search:autocomplete',
    description: 'Autocomplete the addresses by using the indexed data',
)]
final class AddressAutocompleteCommand extends Command
{
    private const int MAX_AUTOCOMPLETE_ITEMS = 20;

    private const int DEFAULT_AUTOCOMPLETE_ITEMS = 10;

    private const int MAX_MIN_SCORE = 100;

    public function __construct(
        private readonly BuildingAddressSearcherInterface $buildingAddressSearcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of items to show', self::DEFAULT_AUTOCOMPLETE_ITEMS)
            ->addOption('min-score', null, InputOption::VALUE_REQUIRED, 'Filter results by their min score (min: 1, max: 100)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = self::MAX_AUTOCOMPLETE_ITEMS;
        if (null !== ($inputLimit = OptionHelper::getPositiveIntOptionValue($input, 'limit', 1, self::MAX_AUTOCOMPLETE_ITEMS))) {
            $limit = min($inputLimit, self::MAX_AUTOCOMPLETE_ITEMS);
        }

        $minScore = null;
        if (null !== ($inputMinScore = OptionHelper::getPositiveIntOptionValue($input, 'min-score', 1, self::MAX_MIN_SCORE))) {
            $minScore = min($inputMinScore, self::MAX_MIN_SCORE);
        }

        $stream = \STDIN;

        // This function is called whenever the input changes and new suggestions are needed.
        $autocompleteCallback = fn(string $userInput): array => $this->autocompleteCallback($userInput, $limit, $minScore);

        $output->write('Address autocomplete: ');
        Autocomplete::autocomplete($output, $stream, $autocompleteCallback, $this->formatResult(...));

        return Command::SUCCESS;
    }

    /**
     * @param int<1, 20>       $limit
     * @param int<1, 100>|null $minScore
     *
     * @return list<BuildingAddressScored>
     */
    private function autocompleteCallback(string $userInput, int $limit, ?int $minScore): array
    {
        if ('' === $userInput || '0' === $userInput) {
            return [];
        }

        $filter = new AddressSearch(
            limit: $limit,
            minScore: $minScore,
            filterByQuery: $userInput,
        );
        $results = iterator_to_array($this->buildingAddressSearcher->searchBuildingAddress($filter));

        return array_values(array_map(static fn(BuildingAddressScored $item): BuildingAddressScored => $item, $results));
    }

    private function formatResult(BuildingAddressScored $match): string
    {
        $matching = str_replace(['<em>', '</em>'], ['<info>', '</info>'], $match->matchingHighlight);
        $score = $match->score;

        return $matching . ' (' .
            "EGID:<comment>{$match->buildingAddress->buildingId}</comment> " .
            "score:<comment>{$score}</comment> " .
            "uuid:<comment>{$match->buildingAddress->id}</comment>" .
            ')';
    }
}
