<?php

declare(strict_types=1);

namespace App\Application\Cli\Registry;

use App\Domain\Registry\Contract\RegistryDataDownloaderInterface;
use App\Domain\Registry\DataLI\RegistryDataDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:registry:li:download',
    description: 'Downloads the Liechtenstein data about buildings',
)]
final class RegistryLiDownloadCommand extends Command
{
    public function __construct(
        #[Autowire(service: RegistryDataDownloader::class)]
        private readonly RegistryDataDownloaderInterface $downloader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $downloaded = $this->downloader->download();

        if ($downloaded) {
            $output->writeln("Downloaded database to <info>{$this->downloader->getDatabaseFilename()}</info>");
        } else {
            $output->writeln("Existing database at <info>{$this->downloader->getDatabaseFilename()}</info> is still up-to-date");
        }

        return Command::SUCCESS;
    }
}
