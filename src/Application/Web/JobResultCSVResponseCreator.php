<?php

declare(strict_types=1);

namespace App\Application\Web;

use App\Application\Web\Contract\JobResultResponseCreatorInterface;
use App\Domain\Resolving\Contract\Result\ResolverResultReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Domain\Resolving\Model\Result\ResolverResult;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Uid\Uuid;

final readonly class JobResultCSVResponseCreator implements JobResultResponseCreatorInterface
{
    private const string ADDITIONAL_DATA_COLUMN_PREFIX = 'userdata.';

    public function __construct(
        private ResolverResultReadRepositoryInterface $resultRepository,
    ) {}

    public function buildResponse(Uuid $jobId, ResolverJob $job): Response
    {
        $out = fopen('php://output', 'w');
        if (!\is_resource($out)) {
            throw new \UnexpectedValueException('Could not open output to write CSV');
        }

        return new StreamedResponse(
            function () use ($jobId, $job, $out): void {
                $header = array_merge(
                    [
                        'id',
                        'confidence',
                        'egid',
                        'edid',
                        'municipality_code',
                        'postal_code',
                        'locality',
                        'street_name',
                        'street_house_number',
                        'latitude',
                        'longitude',
                        'match_type',
                    ],
                    $this->getAdditionalHeadersForJobType($job),
                    $this->getAdditionalHeadersForAdditionalColumns($job),
                );
                fputcsv(
                    stream: $out,
                    fields: $header,
                    escape: '\\',
                );
                foreach ($this->resultRepository->getResults($jobId) as $result) {
                    $row = array_merge(
                        [
                            $result->buildingEntranceId,
                            $result->getConfidenceAsFloat(),
                            $result->buildingId,
                            $result->entranceId,
                            $result->address?->municipalityCode,
                            $result->address?->postalCode,
                            $result->address?->locality,
                            $result->address?->streetName,
                            $result->address?->streetHouseNumber,
                            $result->coordinates?->latitude,
                            $result->coordinates?->longitude,
                            $result->matchType,
                        ],
                        $this->getAdditionalDataForJobType($job, $result),
                        array_values($result->additionalData->getData()),
                    );
                    fputcsv(
                        stream: $out,
                        fields: $row,
                        escape: '\\',
                    );
                }
            },
            Response::HTTP_OK,
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @return list<string>
     */
    private function getAdditionalHeadersForJobType(ResolverJob $job): array
    {
        return match ($job->type) {
            ResolverTypeEnum::ADDRESS_SEARCH => ['original_address'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function getAdditionalDataForJobType(ResolverJob $job, ResolverResult $result): array
    {
        return match ($job->type) {
            ResolverTypeEnum::ADDRESS_SEARCH => [$result->additionalData->getAddressString() ?? ''],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function getAdditionalHeadersForAdditionalColumns(ResolverJob $job): array
    {
        $columns = $job->metadata->additionalColumns ?? [];
        if ([] === $columns) {
            return $columns;
        }

        return array_map(static fn(string $columnName): string => self::ADDITIONAL_DATA_COLUMN_PREFIX . $columnName, $columns);
    }
}
