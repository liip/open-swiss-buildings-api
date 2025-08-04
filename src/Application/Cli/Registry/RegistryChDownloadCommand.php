<?php

declare(strict_types=1);

namespace App\Application\Cli\Registry;

use App\Domain\Registry\Contract\RegistryDataDownloaderInterface;
use App\Domain\Registry\DataCH\RegistryDataDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:registry:ch:download',
    description: 'Downloads the Swiss Federal-Data about buildings',
)]
final class RegistryChDownloadCommand extends Command
{
    public function __construct(
        #[Autowire(service: RegistryDataDownloader::class)]
        private readonly RegistryDataDownloaderInterface $downloader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressBar = null;
        if ($input->isInteractive()) {
            $progressBar = new ProgressBar($output);
        }
        $downloaded = $this->downloader->download($progressBar);

        if ($downloaded) {
            $output->writeln("Downloaded SQLite database to <info>{$this->downloader->getDatabaseFilename()}</info>");
        } else {
            $output->writeln("Existing SQLite database at <info>{$this->downloader->getDatabaseFilename()}</info> is still up-to-date");
        }

        return Command::SUCCESS;
    }
}
