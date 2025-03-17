<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeDecider;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResolveCreateWithAddressSearchController extends AbstractController
{
    public function __construct(
        private readonly ResolverJobFactoryInterface $jobFactory,
        private readonly ResolverJobReadRepositoryInterface $readRepository,
    ) {}

    /**
     * Resolves a list addresses into a list of buildings.
     *
     * The CSV needs to contain the columns `street_housenumbers`, `swisszipcode` and `town`.
     * Additional columns in the CSV will be transferred unchanged to the result list.
     *
     * In the case that the same address is matched multiple times in the CSV with different values in the same
     * additional column, the corresponding additional column values will be merged using `||` in the result list.
     *
     * Addresses are resolved by one of the following matching rules:
     * - Exact match on the full address
     * - Match on the full address using normalized values
     * - When address has a house number suffix (e.g. 3*a*), match on address without a suffix (e.g. 3)
     * - When address has no house number suffix (e.g. 3), match on addresses with any suffix (e.g. 3a, 3b, etc.)
     * - When address has a house number suffix (e.g. 3*a*), match on addresses with another suffix (e.g. 3f, 3g)
     * - When address has a house number, match on address with the closest other house number on
     *   the same street (even if the street crosses into a different municipality)
     * - Address is matched through a search engine, which can detect different writings
     *
     * The result has a match_type column to report which rule was used to find the result, as well as a confidence
     * value to indicate how likely the matching for that row is correct.
     * The result list shows both the matched official address from the building registry and the input address.
     *
     * Addresses that could not be resolved are still included in the result, to allow for debugging.
     * These rows will miss any resolving data.
     */
    #[Route('/resolve/address-search', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        description: 'CSV containing at least the columns `street_housenumbers`, `swisszipcode` and `town`.',
        content: new OA\MediaType(
            mediaType: 'text/csv',
            schema: new OA\Schema(type: 'string', example: "street_housenumbers,swisszipcode,town,extrainformation\nLimmatstrasse 111,8005,Zürich (ZH),\nLimmatstrasse 112,8005,Zürich,B"),
        ),
    )]
    #[OA\Parameter(in: 'header', name: 'content-type', description: 'Content type of the request data, including charset', schema: new OA\Schema(type: 'string', default: 'text/csv; charset=utf-8'))]
    #[OA\Parameter(in: 'header', name: 'csv-delimiter', description: 'Field separator used in the CSV', schema: new OA\Schema(type: 'string', default: ',', maxLength: 1))]
    #[OA\Parameter(in: 'header', name: 'csv-enclosure', description: 'Field enclosure character used in the CSV', schema: new OA\Schema(type: 'string', default: '"', maxLength: 1))]
    #[OA\Response(response: '200', description: 'Successfully created resolver job', content: new Model(type: ResolverJob::class))]
    public function __invoke(Request $request): Response
    {
        $metadata = new ResolverMetadata();

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

        $jobId = $this->jobFactory->create(ResolverTypeEnum::ADDRESS_SEARCH, $request->getContent(true), $metadata);
        $job = $this->readRepository->getJobInfo($jobId->id);

        return new JsonResponse($job, Response::HTTP_OK);
    }
}
