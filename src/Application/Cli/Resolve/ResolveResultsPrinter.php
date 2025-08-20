<?php

declare(strict_types=1);

namespace App\Application\Cli\Resolve;

use App\Application\Web\JobResultCSVResponseCreator;
use App\Application\Web\JobResultJsonResponseCreator;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Result\ResolverResultReadRepositoryInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Domain\Resolving\Model\Result\ResolverResult;
use App\Infrastructure\Pagination;
use App\Infrastructure\Symfony\Console\Paginator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

final readonly class ResolveResultsPrinter
{
    public const int DEFAULT_LIMIT = 20;
    public const string DEFAULT_FORMAT = self::FORMAT_TABLE;

    public const string FORMAT_TABLE = 'table';
    public const string FORMAT_CSV = 'csv';
    public const string FORMAT_CSV_FILE = 'csv-file';
    public const string FORMAT_JSON_FILE = 'json-file';
    public const string FORMAT_NONE = 'none';

    public const array FORMATS = [
        self::FORMAT_TABLE,
        self::FORMAT_CSV,
        self::FORMAT_CSV_FILE,
        self::FORMAT_JSON_FILE,
        self::FORMAT_NONE,
    ];

    public function __construct(
        private ResolverJobReadRepositoryInterface $jobRepository,
        private ResolverResultReadRepositoryInterface $resultRepository,
        private JobResultCSVResponseCreator $csvResponseCreator,
        private JobResultJsonResponseCreator $jsonResponseCreator,
    ) {}

    /**
     * @param positive-int   $limit
     * @param self::FORMAT_* $format
     *
     * @throws ResolverJobNotFoundException
     * @throws \UnexpectedValueException
     */
    public function print(Uuid $jobId, int $limit, string $format, SymfonyStyle $io): void
    {
        if (self::FORMAT_NONE === $format) {
            $io->writeln('Output disabled, do not read results');

            return;
        }

        $job = $this->jobRepository->getJobInfo($jobId);

        if (!$job->isResolved()) {
            throw new \UnexpectedValueException("The job {$job->id} is not resolved yet, it's in state {$job->state->value}");
        }

        match ($format) {
            self::FORMAT_CSV => $this->printCsv($job),
            self::FORMAT_TABLE => $this->printTable($job, $limit, $io),
            self::FORMAT_CSV_FILE => $this->writeCsv($job, $io),
            self::FORMAT_JSON_FILE => $this->writeJson($job, $io),
        };
    }

    private function printCsv(ResolverJob $job): void
    {
        fputcsv(\STDOUT, array_merge(
            [
                'Confidence',
                'ID',
                'Match',
                'Municipality ID',
                'Building ID',
                'Entrance ID',
                'Coordinates',
                'Address',
            ],
            $this->getAdditionalHeadersForJobType($job),
            $job->metadata->additionalColumns ?? [],
        ));
        foreach ($this->resultRepository->getResults(Uuid::fromString($job->id)) as $result) {
            fputcsv(\STDOUT, array_merge(
                [
                    (string) $result->getConfidenceAsFloat(),
                    (string) $result->buildingEntranceId,
                    $result->matchType,
                    $result->address?->municipalityCode,
                    $result->buildingId,
                    $result->entranceId,
                    (string) $result->coordinates,
                    $result->address,
                ],
                $this->getAdditionalDataForJobType($job, $result),
                array_values($result->additionalData->getData()),
            ));
        }
    }

    private function writeCsv(ResolverJob $job, SymfonyStyle $io): void
    {
        $io->write('Writing to ./out.csv ...');
        $response = $this->csvResponseCreator->buildResponse(Uuid::fromString($job->id), $job);
        ob_start();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();
        file_put_contents('out.csv', $content);

        $io->writeln('done');
    }

    private function writeJson(ResolverJob $job, SymfonyStyle $io): void
    {
        $io->write('Writing to ./out.json ...');
        $response = $this->jsonResponseCreator->buildResponse(Uuid::fromString($job->id), $job);
        ob_start();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();
        file_put_contents('out.json', $content);

        $io->writeln('done');
    }

    /**
     * @param positive-int $limit
     */
    private function printTable(ResolverJob $job, int $limit, SymfonyStyle $io): void
    {
        $jobId = Uuid::fromString($job->id);
        $pagination = new Pagination($limit);

        Paginator::paginate($io, $pagination, function (Pagination $pagination) use ($io, $jobId, $job): bool {
            $table = $io->createTable();
            $table->setHeaders(array_merge(
                [
                    'Confidence',
                    'ID',
                    'Match',
                    'Municipality ID',
                    'Building ID',
                    'Entrance ID',
                    'Coordinates',
                    'Address',
                ],
                $this->getAdditionalHeadersForJobType($job),
                $job->metadata->additionalColumns ?? [],
            ));

            $count = 0;
            foreach ($this->resultRepository->getPaginatedResults($jobId, $pagination) as $result) {
                $table->addRow(array_merge(
                    [
                        (string) $result->getConfidenceAsFloat(),
                        (string) $result->buildingEntranceId,
                        $result->getMatchTypeInfo(),
                        $result->address?->municipalityCode,
                        $result->buildingId,
                        $result->entranceId,
                        (string) $result->coordinates,
                        $result->address,
                    ],
                    $this->getAdditionalDataForJobType($job, $result),
                    array_values($result->additionalData->getData()),
                ));
                ++$count;
            }

            $table->render();

            return $count === $pagination->limit;
        });
    }

    /**
     * @return list<string>
     */
    private function getAdditionalHeadersForJobType(ResolverJob $job): array
    {
        return match ($job->type) {
            ResolverTypeEnum::ADDRESS_SEARCH => [
                'Original address',
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function getAdditionalDataForJobType(ResolverJob $job, ResolverResult $result): array
    {
        return match ($job->type) {
            ResolverTypeEnum::ADDRESS_SEARCH => [
                $result->additionalData->getAddressString() ?? '',
            ],
            default => [],
        };
    }

    /**
     * @param positive-int $limit
     *
     * @throws ResolverJobNotFoundException
     * @throws \UnexpectedValueException
     */
    public function printDebug(Uuid $jobId, int $limit, SymfonyStyle $io): void
    {
        $job = $this->jobRepository->getJobInfo($jobId);

        if (!$job->isResolved()) {
            throw new \UnexpectedValueException("The job {$job->id} is not resolved yet, it's in state {$job->state->value}");
        }

        foreach ($this->resultRepository->getPaginatedResults($jobId, new Pagination($limit)) as $result) {
            match ($job->type) {
                ResolverTypeEnum::BUILDING_IDS => $this->printDebugForBuildingIds($result, $io),
                ResolverTypeEnum::MUNICIPALITIES_CODES => $this->printDebugForMunicipalityCodes($result, $io),
                ResolverTypeEnum::GEO_JSON => $this->printDebugForGeoJson($result, $io),
                ResolverTypeEnum::ADDRESS_SEARCH => $this->printDebugForAddressSearch($result, $io),
            };
        }
    }

    private function printDebugForBuildingIds(ResolverResult $result, SymfonyStyle $io): void
    {
        if (null === $result->entranceId) {
            $io->definitionList(
                ['Match' => 'No match'],
                ['Building ID' => $result->buildingId],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        } else {
            $io->definitionList(
                ['Match' => $result->getMatchTypeInfo()],
                ['ID' => (string) $result->buildingEntranceId],
                ['Building ID' => $result->buildingId],
                ['Entrance ID' => $result->entranceId],
                ['Address' => (string) $result->address],
                ['Coordinates' => (string) $result->coordinates],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        }
    }

    private function printDebugForMunicipalityCodes(ResolverResult $result, SymfonyStyle $io): void
    {
        if (null === $result->entranceId) {
            $io->definitionList(
                ['Match' => 'No match'],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        } else {
            $io->definitionList(
                ['Match' => $result->getMatchTypeInfo()],
                ['ID' => (string) $result->buildingEntranceId],
                ['Municipality' => $result->address?->municipalityCode],
                ['Building ID' => $result->buildingId],
                ['Entrance ID' => $result->entranceId],
                ['Address' => (string) $result->address],
                ['Coordinates' => (string) $result->coordinates],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        }
    }

    private function printDebugForGeoJson(ResolverResult $result, SymfonyStyle $io): void
    {
        if (null === $result->entranceId) {
            $io->definitionList(
                ['Match' => 'No match'],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        } else {
            $io->definitionList(
                ['Match' => $result->getMatchTypeInfo()],
                ['ID' => (string) $result->buildingEntranceId],
                ['Building ID' => $result->buildingId],
                ['Entrance ID' => $result->entranceId],
                ['Address' => (string) $result->address],
                ['Coordinates' => (string) $result->coordinates],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        }
    }

    private function printDebugForAddressSearch(ResolverResult $result, SymfonyStyle $io): void
    {
        if (null === $result->entranceId) {
            $io->definitionList(
                ['Original address' => $result->additionalData->getAddressString() ?? ''],
                ['Match' => $result->matchType],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        } else {
            $io->definitionList(
                ['Original address' => $result->additionalData->getAddressString() ?? ''],
                ['Match' => $result->getMatchTypeInfo()],
                ['ID' => (string) $result->buildingEntranceId],
                ['Building ID' => $result->buildingId],
                ['Entrance ID' => $result->entranceId],
                ['Address' => (string) $result->address],
                ['Coordinates' => (string) $result->coordinates],
                ['Confidence' => (string) $result->getConfidenceAsFloat()],
            );
        }
    }
}
