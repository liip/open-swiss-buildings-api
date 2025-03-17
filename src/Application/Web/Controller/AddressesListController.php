<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Contract\BuildingAddressFinderInterface;
use App\Application\Contract\BuildingAddressStatsProviderInterface;
use App\Application\Web\Model\AddressListQueryString;
use App\Application\Web\Model\AddressListResponse;
use App\Infrastructure\Pagination;
use App\Infrastructure\SchemaOrg\Place;
use App\Infrastructure\SchemaOrg\PlaceLinearizer;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeDecider;
use App\Infrastructure\Symfony\HttpFoundation\RequestContentTypeEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class AddressesListController extends AbstractController
{
    public function __construct(
        private readonly BuildingAddressFinderInterface $buildingAddressFinder,
        private readonly BuildingAddressStatsProviderInterface $statsProvider,
    ) {}

    /**
     * Returns a segment of the whole list of address known to the application.
     *
     * The response model corresponds to [https://schema.org/Place](https://schema.org/Place).
     */
    #[Route('/addresses', methods: ['GET'])]
    #[OA\Response(
        response: '200',
        description: 'Returns a segment of the whole list of address known to the application. ' .
                     'Addresses are sorted by their Identifier in ascending order.',
        content: [
            new OA\MediaType(
                mediaType: 'text/csv',
                schema: new OA\Schema(
                    type: 'string',
                    example: 'identifier,postalAddress.addressLocality,postalAddress.addressRegion,postalAddress.postalCode,postalAddress.streetAddress,postalAddress.inLanguage,geo.latitude,geo.longitude,additionalProperty.buildingId,additionalProperty.entranceId,additionalProperty.addressId,additionalProperty.municipalityCode'
                    . "\n" .
                    '018f05aa-3539-7d15-93ca-d97860c4ecd7,"Affoltern am Albis","Affoltern am Albis",8910,"Grossholzerstrasse 20",de,47.269117135498,8.4490957266308,1,0,100000334,2',
                ),
            ),
            new OA\JsonContent(ref: new Model(type: AddressListResponse::class)),
        ],
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Maximum number of results to return. Must be between 1 and 2000, defaults to 25.',
        in: 'query',
        schema: new OA\Schema(type: 'int'),
        example: 25,
    )]
    public function __invoke(
        Request $request,
        #[MapQueryString]
        AddressListQueryString $listQueryString = new AddressListQueryString(),
    ): Response {
        $pagination = new Pagination(limit: $listQueryString->limit, offset: $listQueryString->offset);
        $results = $this->buildingAddressFinder->findPlaces($pagination);

        $contentType = RequestContentTypeDecider::decideContentType($request, RequestContentTypeEnum::JSON);

        return match ($contentType) {
            RequestContentTypeEnum::WILDCARD,
            RequestContentTypeEnum::JSON => $this->resultsAsJson($results),
            RequestContentTypeEnum::CSV => $this->resultsAsCsv($results),
        };
    }

    /**
     * @param iterable<Place> $results
     */
    private function resultsAsJson(iterable $results): StreamedJsonResponse
    {
        $total = $this->statsProvider->countIndexedAddresses();

        $response = new AddressListResponse(total: $total, results: $results);

        return new StreamedJsonResponse($response->toIterable());
    }

    /**
     * @param iterable<Place> $results
     */
    private function resultsAsCSV(iterable $results): StreamedResponse
    {
        $out = fopen('php://output', 'w');
        if (!\is_resource($out)) {
            throw new \UnexpectedValueException('Could not open output to write CSV');
        }

        return new StreamedResponse(
            static function () use ($results, $out): void {
                fputcsv($out, PlaceLinearizer::headers());
                foreach ($results as $result) {
                    fputcsv($out, array_values(PlaceLinearizer::linearized($result)));
                }
            },
            Response::HTTP_OK,
            ['Content-Type' => 'text/csv'],
        );
    }
}
