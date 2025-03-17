<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Web\Contract\JobResultResponseCreatorInterface;
use App\Application\Web\JobResultCSVResponseCreator;
use App\Application\Web\JobResultJsonResponseCreator;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Result\ResolverResult;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeDecider;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class ResolveJobResultsController extends AbstractController
{
    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
        #[Autowire(service: JobResultCSVResponseCreator::class)]
        private readonly JobResultResponseCreatorInterface $csvResponseCreator,
        #[Autowire(service: JobResultJsonResponseCreator::class)]
        private readonly JobResultResponseCreatorInterface $jsonResponseCreator,
    ) {}

    /**
     * Returns the results of a resolver job.
     *
     * The results are sorted by building ID ("EGID").
     *
     * When requesting a CSV, it will contain the following columns:
     * * `id`: Unique stable identifier of the address
     * * `egid`: Swiss building ID
     * * `edid`: Swiss building entrance ID
     * * `municipality_code`: Code of the municipality ("BFS Gemeindenummer")
     * * `postal_code`: Swiss postal code
     * * `locality`: Locality of the address
     * * `street_name`: Name of the street
     * * `street_house_number`: House number of the address
     * * `original_address`: Address of input data, only set for address search resolving
     * * `matching`: Information about address matching, only set for address search resolving
     * * `confidence`: Confidence of the match between 0 and 1
     * * `match_type`: Type of the match for each result
     * * `latitude`: Latitude in WGS84
     * * `longitude`: Longitude in WGS84
     *
     * Any additional columns coming from the input data will be appended.
     * When exporting as CSV, the header columns with user data are prefixed with `userdata.` to avoid name collisions.
     *
     * In case of no matches are found for the given resolving job an empty row will be returned as results, for each
     * matching request provided in the original request.
     * This is provided to help the client with assessing which part of the resolving did not find any results.
     * For example a specific EGID, a municipality or a Polygon in a GeoJson resolve job.
     * Each row will contain the specific characteristic of the request: the egid, municipality_id or original_address
     * respectively, plus the additional data provided by the user.
     * The id column and the columns with the information from the buildings registry will be empty for that case.
     */
    #[Route('/resolve/jobs/{id}/results', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Result list of the given resolver job',
        content: [
            new OA\MediaType(
                mediaType: 'text/csv',
                schema: new OA\Schema(
                    type: 'string',
                    example: 'id,egid,edid,municipality_code,postal_code,locality,street_name,street_house_number,original_address,matching,confidence,latitude,longitude,match_type,userdata.group' .
                             "\n018e417d-ed8d-73e8-9efa-719fa94a2eb4,9011206,0,261,8005,Zürich,Limmatstrasse,111,\"Limmatstrasse 111, 8005 Zürich (ZH)\",<em>Limmatstrasse</em> <em>111</em> <em>8005</em> <em>Zürich</em>,0.8,1.2,13.8,exact," .
                             "\n018e4178-496a-7839-a565-0513b61c0ae0,150404,0,261,8005,Zürich,Limmatstrasse,112,\"Limmatstrasse 112, 8005 Zürich\",<em>Limmatstrasse</em> <em>112</em> <em>8005</em> <em>Zürich</em>,1,2.5,14.1,exact,B",
                ),
            ),
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'results',
                        type: 'array',
                        items: new OA\Items(ref: new Model(type: ResolverResult::class)),
                    ),
                ],
                type: 'object',
            ),
        ],
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Job with given ID was not found',
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'No results can be fetched for the given job',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'error',
                    type: 'string',
                ),
            ],
            type: 'object',
        ),
    )]
    public function __invoke(Request $request, string $id): Response
    {
        try {
            $jobId = Uuid::fromString($id);
            $job = $this->jobRepository->getJobInfo($jobId);
        } catch (\InvalidArgumentException|\UnexpectedValueException|ResolverJobNotFoundException) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $contentType = RequestContentTypeDecider::decideContentType($request, RequestContentTypeEnum::JSON);

        if (!$job->isResolved()) {
            return new JsonResponse(['error' => "Job is not resolved yet, it's in state {$job->state->value}"], Response::HTTP_BAD_REQUEST);
        }

        // Increase time limit to be able to return big results
        set_time_limit(60 * 10);

        return match ($contentType) {
            RequestContentTypeEnum::WILDCARD,
            RequestContentTypeEnum::JSON => $this->jsonResponseCreator->buildResponse($jobId, $job),
            RequestContentTypeEnum::CSV => $this->csvResponseCreator->buildResponse($jobId, $job),
        };
    }
}
