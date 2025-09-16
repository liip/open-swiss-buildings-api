<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverTask;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Pagination;
use App\Infrastructure\Symfony\Console\Paginator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:resolve:tasks:list',
    description: 'Displays the list of tasks for a single resolver job',
)]
final class ResolveTasksListCommand
{
    private const int DEFAULT_LIMIT = 20;

    private const string FORMAT_TABLE = 'table';
    private const string FORMAT_CSV = 'csv';

    private const array FORMATS = [
        self::FORMAT_TABLE,
        self::FORMAT_CSV,
    ];

    private const string DEFAULT_FORMAT = self::FORMAT_TABLE;

    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
        private readonly ResolverTaskReadRepositoryInterface $taskRepository,
    ) {}

    private static function listFormats(): string
    {
        return implode('|', self::FORMATS);
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'ID of the resolver job')]
        string $jobId,
        #[Option(description: 'Number of rows to load')]
        int $limit = self::DEFAULT_LIMIT,
        #[Option(description: 'Format of the output, one of table|csv')]
        string $format = self::DEFAULT_FORMAT,
    ): int {
        $io = new SymfonyStyle($input, $output);

        try {
            $jobId = Uuid::fromString($jobId);
            $job = $this->jobRepository->getJobInfo($jobId);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!$job->isPrepared()) {
            $io->error("The job {$job->id} is not prepared yet, it's in state {$job->state->value}");

            return Command::FAILURE;
        }

        if (self::FORMAT_CSV === $format) {
            fputcsv(\STDOUT, array_merge(
                ['ID'],
                $this->getHeaders($job),
                $job->metadata->additionalColumns ?? [],
            ));
            foreach ($this->taskRepository->getTasks($jobId) as $task) {
                fputcsv(\STDOUT, array_merge(
                    [(string) $task->id],
                    $this->getData($job, $task),
                    array_values($task->additionalData->getData()),
                ));
            }

            return Command::SUCCESS;
        }

        $pagination = new Pagination($limit);

        Paginator::paginate($io, $pagination, function (Pagination $pagination) use ($io, $jobId, $job): bool {
            $table = $io->createTable();
            $table->setHeaders(array_merge(
                [
                    'ID',
                    'Confidence',
                ],
                $this->getHeaders($job),
                $job->metadata->additionalColumns ?? [],
            ));

            $count = 0;
            foreach ($this->taskRepository->getPaginatedTasks($jobId, $pagination) as $task) {
                $table->addRow(array_merge(
                    [
                        (string) $task->id,
                        (string) $task->getConfidenceAsFloat(),
                    ],
                    $this->getData($job, $task),
                    array_values($task->additionalData->getData()),
                ));
                ++$count;
            }

            $table->render();

            return $count === $pagination->limit;
        });

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function getHeaders(ResolverJob $job): array
    {
        return match ($job->type) {
            ResolverTypeEnum::BUILDING_IDS => [
                'Building ID',
            ],
            ResolverTypeEnum::MUNICIPALITIES_CODES => [
                'Municipality',
            ],
            ResolverTypeEnum::ADDRESS_SEARCH => [
                'Address',
                'Building ID',
                'Entrance ID',
            ],
            ResolverTypeEnum::GEO_JSON => [
                'GeoJson',
            ],
        };
    }

    /**
     * @return list<string>
     */
    private function getData(ResolverJob $job, ResolverTask $task): array
    {
        return match ($job->type) {
            ResolverTypeEnum::BUILDING_IDS => [
                $task->matchingBuildingId ?? '',
            ],
            ResolverTypeEnum::MUNICIPALITIES_CODES => [
                $task->matchingMunicipalityCode ?? '',
            ],
            ResolverTypeEnum::ADDRESS_SEARCH => [
                $task->additionalData->getAddressString() ?? '',
                $task->matchingBuildingId ?? '',
                $task->matchingEntranceId ?? '',
            ],
            ResolverTypeEnum::GEO_JSON => [
                '-geojson-',
            ],
        };
    }
}
