<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Cli;

use App\Infrastructure\Meilisearch\Contract\IndexProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'meilisearch:tasks:get',
    description: 'Get a given Task from Meilisearch engine',
)]
final class TasksGetCommand extends Command
{
    public function __construct(
        private readonly IndexProviderInterface $indexProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $task = $this->indexProvider->getBuildingEntranceIndex()->getTask($id);

        dump($task);

        return Command::SUCCESS;
    }
}
