<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeDecider;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResolveCreateWithBuildingIdsController extends AbstractController
{
    public function __construct(
        private readonly ResolverJobFactoryInterface $jobFactory,
        private readonly ResolverJobReadRepositoryInterface $readRepository,
    ) {}

    /**
     * Resolves a list of Swiss building IDs ("EGID") into a list of buildings.
     *
     * The CSV needs to contain the column `egid`.
     * Additional columns in the CSV will be transferred unchanged to the result list.
     *
     * In the case that a Swiss building ID appears multiple times in the CSV with different values in the same
     * additional column, the corresponding additional column values will be merged using `||`.
     *
     * Swiss building IDs with multiple entrances will be expanded to all available entrances in the result list.
     *
     * Swiss building IDs that could not be matched are still included in the result, to allow for debugging.
     * These rows will miss any resolving data.
     */
    #[Route('/resolve/building-ids', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        description: 'CSV containing at least the column `egid`.',
        content: new OA\MediaType(
            mediaType: 'text/csv',
            schema: new OA\Schema(type: 'string', example: "egid,extrainformation\n9011206,1\n9083913,2"),
        ),
    )]
    #[OA\Parameter(in: 'header', name: 'content-type', description: 'Content type of the request data, including charset', schema: new OA\Schema(type: 'string', default: 'text/csv; charset=utf-8'))]
    #[OA\Parameter(in: 'header', name: 'csv-delimiter', description: 'Field separator used in the CSV', schema: new OA\Schema(type: 'string', default: ',', maxLength: 1))]
    #[OA\Parameter(in: 'header', name: 'csv-enclosure', description: 'Field enclosure character used in the CSV', schema: new OA\Schema(type: 'string', default: '"', maxLength: 1))]
    #[OA\Response(response: '200', description: 'Successfully created resolver job', content: new Model(type: ResolverJob::class))]
    public function __invoke(Request $request): JsonResponse
    {
        $metadata = new ResolverMetadata(filterByCountry: CountryCodeEnum::CH);

        $contentType = RequestContentTypeDecider::getContentType($request);
        if (null !== $contentType) {
            if ('text/csv' !== $contentType->type) {
                return new JsonResponse(['error' => "Only text/csv is accepted as content type, but {$contentType->type} was specified"], Response::HTTP_NOT_ACCEPTABLE);
            }
            if (null !== $contentType->charset) {
                $metadata = $metadata->withCharset($contentType->charset);
            }
        }

        if (null !== ($delimiter = $request->headers->get('csv-delimiter')) && '' !== $delimiter) {
            $metadata = $metadata->withCsvDelimiter($delimiter);
        }
        if (null !== ($enclosure = $request->headers->get('csv-enclosure')) && '' !== $enclosure) {
            $metadata = $metadata->withCsvEnclosure($enclosure);
        }

        $jobId = $this->jobFactory->create(ResolverTypeEnum::BUILDING_IDS, $request->getContent(true), $metadata);
        $job = $this->readRepository->getJobInfo($jobId->id);

        return new JsonResponse($job, Response::HTTP_OK);
    }
}
