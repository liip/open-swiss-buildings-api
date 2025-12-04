<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler\AddressSearch;

use App\Domain\Resolving\Contract\CsvReaderFactoryInterface;
use App\Domain\Resolving\Contract\Job\JobPreparerInterface;
use App\Domain\Resolving\Contract\Job\ResolverAddressWriteRepositoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverMetadataWriteRepositoryInterface;
use App\Domain\Resolving\Exception\CsvReadException;
use App\Domain\Resolving\Exception\InvalidInputDataException;
use App\Domain\Resolving\Model\AdditionalData;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use App\Domain\Resolving\Model\Job\WriteResolverAddress;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Psr\Log\LoggerInterface;

final readonly class AddressSearchPreparer implements JobPreparerInterface
{
    private const string CSV_HEADER_STREET = 'street_housenumbers';

    private const string CSV_HEADER_POSTAL_CODE = 'swisszipcode';

    private const string CSV_HEADER_LOCALITY = 'town';

    public function __construct(
        private ResolverAddressWriteRepositoryInterface $addressRepository,
        private ResolverMetadataWriteRepositoryInterface $metadataRepository,
        private CsvReaderFactoryInterface $csvReaderFactory,
        private LoggerInterface $logger,
    ) {}

    public function canPrepareJob(ResolverTypeEnum $type): bool
    {
        return ResolverTypeEnum::ADDRESS_SEARCH === $type;
    }

    public function prepareJob(ResolverJobRawData $jobData): void
    {
        $addresses = $this->readTasks($jobData);

        try {
            $this->addressRepository->store($addresses);
        } catch (\Exception $e) {
            throw InvalidInputDataException::wrap($e);
        }
    }

    /**
     * @return iterable<WriteResolverAddress>
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

        if (!\in_array(self::CSV_HEADER_STREET, $header, true)
            || !\in_array(self::CSV_HEADER_POSTAL_CODE, $header, true)
            || !\in_array(self::CSV_HEADER_LOCALITY, $header, true)
        ) {
            throw new InvalidInputDataException('Header columns "' . self::CSV_HEADER_STREET . '", "' . self::CSV_HEADER_POSTAL_CODE . '" and "' . self::CSV_HEADER_LOCALITY . '" are required, found headers are "' . implode(',', $header) . '"!');
        }

        $this->logger->info('Using CSV delimiter {csv_delimiter} and enclosure {csv_enclosure} for job {job_id}', [
            'job_id' => (string) $jobData->id,
            'csv_delimiter' => $reader->getDelimiter(),
            'csv_enclosure' => $reader->getEnclosure(),
        ]);
        $metadata = $jobData->metadata->withCsvDelimiter($reader->getDelimiter())
            ->withCsvEnclosure($reader->getEnclosure())
        ;
        $additionalColumns = array_values(array_filter($header, static fn(string $h): bool => !\in_array($h, [self::CSV_HEADER_STREET, self::CSV_HEADER_POSTAL_CODE, self::CSV_HEADER_LOCALITY], true)));
        if ([] !== $additionalColumns) {
            $metadata = $metadata->withAdditionalColumns($additionalColumns);
        }
        $this->metadataRepository->updateMetadata($jobData->id, $metadata);

        try {
            foreach ($reader->read() as $row) {
                $matchingStreet = $row->get(self::CSV_HEADER_STREET);
                if ('' === $matchingStreet) {
                    throw new InvalidInputDataException("Row #{$row->number} does not contain a street!");
                }

                $additionalDataValues = [];
                foreach ($additionalColumns as $name) {
                    $additionalDataValues[$name] = '';
                    if (\array_key_exists($name, $row->data)) {
                        $additionalDataValues[$name] = $row->data[$name];
                    }
                }

                $additionalData = AdditionalData::create($additionalDataValues);

                $matchingStreet = $this->cleanValue($matchingStreet);
                $matchingPostalCode = $this->cleanValue($row->get(self::CSV_HEADER_POSTAL_CODE));
                $matchingLocality = $this->cleanValue($row->get(self::CSV_HEADER_LOCALITY));

                $additionalData->withAddress($matchingStreet, $matchingPostalCode, $matchingLocality);

                yield new WriteResolverAddress(
                    jobId: $jobData->id,
                    street: $matchingStreet,
                    postalCode: $matchingPostalCode,
                    locality: $matchingLocality,
                    additionalData: $additionalData,
                );
            }
        } catch (CsvReadException $e) {
            throw InvalidInputDataException::wrap($e);
        } catch (\InvalidArgumentException $e) {
            if (isset($row)) {
                throw new InvalidInputDataException("Row #{$row->number} contains invalid data ({$e->getMessage()})", $e);
            }
            throw InvalidInputDataException::wrap($e);
        }
    }

    private function cleanValue(string $value): string
    {
        // Remove multiple spaces
        $v = preg_replace('/\s+/', ' ', $value);
        if (null === $v) {
            return $value;
        }

        return trim($v);
    }
}
