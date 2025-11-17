<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Web\Model\ResolveGeoJsonCreateQueryString;
use App\Domain\Resolving\Contract\Job\ResolverJobFactoryInterface;
use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ResolveCreateWithGeoJsonController extends AbstractController
{
    public function __construct(
        private readonly ResolverJobFactoryInterface $jobFactory,
        private readonly ResolverJobReadRepositoryInterface $readRepository,
    ) {}

    /**
     * Resolves a GeoJSON with polygons into a list of buildings.
     *
     * To provide additional information, you can define `properties` inside each feature. Each of those properties is
     * added as an additional column to the result.
     *
     * In the case that a building is included in multiple polygons with different values for the same property, the
     * values of the corresponding column will be merged using `||` in the result list.
     */
    #[Route('/resolve/geo-json', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        description: 'Valid GeoJSON data',
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(type: 'object', example: "{\n    \"type\": \"FeatureCollection\",\n    \"name\": \"abschaltgebiet\",\n    \"crs\": { \"type\": \"name\", \"properties\": { \"name\": \"urn:ogc:def:crs:EPSG::2056\" } },\n    \"features\": [\n        {\n            \"type\": \"Feature\",\n            \"properties\": { \"extrainformation\": \"B\" },\n            \"geometry\": { \"type\": \"MultiPolygon\", \"coordinates\": [ [ [ [ 2673182.567755069583654, 1270003.15436748531647 ], [ 2676674.704157737549394, 1269564.568515965249389 ], [ 2678160.10340958321467, 1268124.682890220778063 ], [ 2676161.641463506501168, 1266775.824516678228974 ], [ 2672189.543185590300709, 1267396.464872602606192 ], [ 2673182.567755069583654, 1270003.15436748531647 ] ] ] ] }\n         }\n    ]\n}"),
        ),
    )]
    #[OA\Response(response: '200', description: 'Successfully created resolver job', content: new Model(type: ResolverJob::class))]
    public function __invoke(
        Request $request,
        #[MapQueryString]
        ?ResolveGeoJsonCreateQueryString $queryString = null,
    ): JsonResponse {
        $metadata = new ResolverMetadata();
        if (null !== $srid = $queryString?->srid) {
            $metadata = $metadata->withGeoJsonSRID($srid->value);
        }

        $jobId = $this->jobFactory->create(ResolverTypeEnum::GEO_JSON, $request->getContent(true), $metadata);
        $job = $this->readRepository->getJobInfo($jobId->id);

        return new JsonResponse($job, Response::HTTP_OK);
    }
}
