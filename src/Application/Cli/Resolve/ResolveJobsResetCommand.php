<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverAddressMatchWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressStreetWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Result\ResolverResultWriteRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Symfony\Console\OptionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:jobs:reset',
    description: 'Reset a given Job',
)]
final class ResolveJobsResetCommand extends Command
{
    private const array STATES = [
        ResolverJobStateEnum::CREATED->value,
        ResolverJobStateEnum::READY->value,
    ];

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobReadRepository,
        private readonly ResolverJobWriteRepositoryInterface $jobWriteRepository,
        private readonly ResolverResultWriteRepositoryInterface $resultRepository,
        private readonly ResolverTaskWriteRepositoryInterface $taskRepository,
        private readonly ResolverAddressWriteRepositoryInterface $addressRepository,
        private readonly ResolverAddressMatchWriteRepositoryInterface $addressMatchRepository,
        private readonly ResolverAddressStreetWriteRepositoryInterface $addressStreetRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $allowedStates = implode('|', self::STATES);
        $this
            ->addArgument('jobId', InputArgument::REQUIRED, 'ID of the Job')
            ->addOption(
                'state',
                null,
                InputOption::VALUE_REQUIRED,
                "Reset the job to that given state, possible values ({$allowedStates})",
                ResolverJobStateEnum::CREATED->value,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = (string) $input->getArgument('jobId');
        $state = OptionHelper::getStringBackedEnumOptionValue($input, 'state', ResolverJobStateEnum::class);

        $io = new SymfonyStyle($input, $output);

        try {
            $jobId = Uuid::fromString($jobId);
            $job = $this->jobReadRepository->getJobIdentifier($jobId);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $deleted = [];

        switch ($state) {
            case ResolverJobStateEnum::CREATED:
                $deleted['addresses'] = $this->addressRepository->deleteByJobId($jobId);
                $deleted['tasks'] = $this->taskRepository->deleteByJobId($jobId);
                $deleted['results'] = $this->resultRepository->deleteByJobId($jobId);
                $this->jobWriteRepository->markJobAsCreated($jobId);
                break;

            case ResolverJobStateEnum::READY:
                if (ResolverTypeEnum::ADDRESS_SEARCH === $job->type) {
                    $deleted['tasks'] = $this->taskRepository->deleteByJobId($jobId);
                    $deleted['matches'] = $this->addressMatchRepository->deleteByJobId($jobId);
                    $deleted['streets'] = $this->addressStreetRepository->deleteByJobId($jobId);
                }
                $deleted['results'] = $this->resultRepository->deleteByJobId($jobId);
                $this->jobWriteRepository->markJobAsReady($jobId);
                break;

            default:
                $allowedStates = implode('|', self::STATES);
                throw new \UnexpectedValueException("State must be one of {$allowedStates}, got: {$state?->value}");
        }

        foreach ($deleted as $what => $count) {
            $io->writeln("Deleted <info>{$count}</info> {$what} for Job {$jobId}");
        }

        return Command::SUCCESS;
    }
}
