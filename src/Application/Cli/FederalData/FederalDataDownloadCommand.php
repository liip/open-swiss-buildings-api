<?php

declare(strict_types=1);

namespace App\Application\Cli\FederalData;

use App\Domain\FederalData\Contract\FederalDataDownloaderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:federal-data:download',
    description: 'Downloads the Federal-Data about buildings',
)]
final class FederalDataDownloadCommand extends Command
{
    public function __construct(
        private readonly FederalDataDownloaderInterface $downloader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $downloaded = $this->downloader->download();

        if ($downloaded) {
            $output->writeln("Downloaded SQLite database to <info>{$this->downloader->getDatabaseFilename()}</info>");
        } else {
            $output->writeln("Existing SQLite database at <info>{$this->downloader->getDatabaseFilename()}</info> is still up-to-date");
        }

        return Command::SUCCESS;
    }
}
