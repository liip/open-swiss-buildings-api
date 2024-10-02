<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\MunicipalitiesCodes;

use App\Domain\Resolving\Contract\CsvReaderFactoryInterface;
use App\Domain\Resolving\Contract\Job\JobPreparerInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverTaskWriteRepositoryInterface;
use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\WriteResolverTask;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Psr\Log\LoggerInterface;

final readonly class MunicipalitiesCodesPreparer implements JobPreparerInterface
{
    private const string CSV_HEADER_MUNICIPALITY_CODE = 'bfsnumber';

    public function __construct(
        private ResolverTaskWriteRepositoryInterface $taskRepository,
        private ResolverMetadataWriteRepositoryInterface $metadataRepository,
        private CsvReaderFactoryInterface $csvReaderFactory,
        private LoggerInterface $logger,
    ) {}

    public function canPrepareJob(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::MUNICIPALITIES_CODES === $type;
    }

    public function prepareJob(ResolverJobRawData $jobData): void
    {
        $tasks = $this->readTasks($jobData);
        $this->taskRepository->store($tasks);
    }

    /**
     * @return iterable<WriteResolverTask>
     *
     * @throws InvalidInputDataException
     */
    private function readTasks(ResolverJobRawData $jobData): iterable
    {
        $data = $jobData->getResource();

        $reader = $this->csvReaderFactory->createReader($data, $jobData->metadata->csvDelimiter, $jobData->metadata->csvEnclosure, $jobData->metadata->charset);
        try {
            $header = $reader->getHeader();
        } catch (CsvReadException $e) {
            throw InvalidInputDataException::wrap($e);
        }

        if (!\in_array(self::CSV_HEADER_MUNICIPALITY_CODE, $header, true)) {
            throw new InvalidInputDataException('Header column "' . self::CSV_HEADER_MUNICIPALITY_CODE . '" is required, found headers are "' . implode(',', $header) . '"!');
        }

        $this->logger->info('Using CSV delimiter {csv_delimiter} and enclosure {csv_enclosure} for job {job_id}', [
            'job_id' => (string) $jobData->id,
            'csv_delimiter' => $reader->getDelimiter(),
            'csv_enclosure' => $reader->getEnclosure(),
        ]);
        $metadata = $jobData->metadata->withCsvDelimiter($reader->getDelimiter())
            ->withCsvEnclosure($reader->getEnclosure())
        ;
        $additionalColumns = array_values(array_filter($header, static fn(string $h): bool => self::CSV_HEADER_MUNICIPALITY_CODE !== $h));
        if ([] !== $additionalColumns) {
            $metadata = $metadata->withAdditionalColumns($additionalColumns);
        }
        $this->metadataRepository->updateMetadata($jobData->id, $metadata);

        try {
            foreach ($reader->read() as $row) {
                $municipalityCode = $row->get(self::CSV_HEADER_MUNICIPALITY_CODE);
                if ('' === $municipalityCode) {
                    throw new InvalidInputDataException("Row #{$row->number} does not contain a municipality ID!");
                }
                $additionalData = [];
                foreach ($additionalColumns as $name) {
                    $additionalData[$name] = '';
                    if (\array_key_exists($name, $row->data)) {
                        $additionalData[$name] = $row->data[$name];
                    }
                }

                yield WriteResolverTask::forMunicipalityCode(
                    jobId: $jobData->id,
                    confidence: 100,
                    matchingMunicipalityCode: $municipalityCode,
                    additionalData: AdditionalData::create($additionalData),
                );
            }
        } catch (CsvReadException $e) {
            throw InvalidInputDataException::wrap($e);
        }
    }
}
