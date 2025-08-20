<?php

declare(strict_types=1);

namespace App\Application\Cli\AddressSearch;

use App\Application\Contract\BuildingAddressIndexerInterface;
use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:address-search:index',
    description: 'Index (or update) a single BuildingAddress into the search index',
)]
final readonly class AddressSearchIndexCommand
{
    public function __construct(
        private BuildingAddressIndexerInterface $addressIndexer,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'The BuildingAddress ID to index')]
        ?string $id = null,
        #[Option(description: 'The file containing one BuildingAddress ID per line to be imported', name: 'from-file')]
        ?string $file = null,
    ): int {
        $io = new SymfonyStyle($input, $output);
        if (!$file && !$id) {
            $io->error([
                'No source of BuildingAddress ID was provided',
                'Please specify a building address ID or a file to import IDs from',
            ]);

            return Command::FAILURE;
        }

        $total = $indexed = $failed = 0;
        if ($id) {
            ++$total;
            $this->indexUuid($id, $io) ? ++$indexed : ++$failed;
        }

        if ($file) {
            if (file_exists($file) && false !== $contents = file_get_contents($file)) {
                $token = strtok($contents, "\n");
                while (false !== $token) {
                    ++$total;
                    $this->indexUuid($token, $io) ? $indexed++ : $failed++;
                    $token = strtok("\n");
                }
            } else {
                $io->error("Unable to read contents of file {$file}");
            }
        }

        $io->writeln("Hadled <info>{$total}</info> items, {$failed} failed");

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }

    private function indexUuid(string $id, SymfonyStyle $io): bool
    {
        $io->write("Indexing ID <comment>{$id}</comment>: ");
        try {
            $uuid = Uuid::fromString($id);
            $this->addressIndexer->indexById($uuid);
            $io->writeln('<info>done</info>');

            return true;
        } catch (BuildingAddressNotFoundException $e) {
            $io->writeln('<error>Error, UUID could not be found</error>');
        } catch (\InvalidArgumentException $e) {
            $io->writeln('<error>Error, Invalid UUID</error>');
        }

        return false;
    }
}
