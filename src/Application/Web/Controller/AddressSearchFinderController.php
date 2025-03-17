<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Application\Web\Model\AddressSearchFinderQueryString;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\PlaceScored;
use App\Infrastructure\Model\CountryCodeEnum;
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

final class AddressSearchFinderController extends AbstractController
{
    public function __construct(
        private readonly BuildingAddressSearcherInterface $buildingAddressReader,
    ) {}

    /**
     * Searches for addresses.
     *
     * This search uses normal string search with similarities, without using specific
     * tweaks with domain knowledge of how addresses work.
     * The call is suitable for autocomplete search while a user types an address.
     *
     * The response model corresponds to [https://schema.org/Place](https://schema.org/Place).
     */
    #[Route('/address-search/find', methods: ['GET'])]
    #[OA\Response(
        response: '200',
        description: 'List of matched places',
        content: [
            new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'hits',
                        type: 'array',
                        items: new OA\Items(ref: new Model(type: PlaceScored::class)),
                    ),
                ],
                type: 'object',
            ),
            new OA\MediaType(
                mediaType: 'text/csv',
                schema: new OA\Schema(type: 'string', example: "score,identifier,country_code,address_id,building_id,entrance_id,address,latitude,longitude\n95,018e4183-b201-7575-81bf-7799da1be5d6,101731502,302048818,0,\"Limmatstrasse 264, 8005 Zürich\",32.124486490902,-19.91799616386\n90,018e4183-b201-7575-81bf-7799d9427b9d,101731501,302048817,1,\"Limmatstrasse 264a, 8005 Zürich\",32.124486490902,-19.91799616386\n"),
            ),
        ],
    )]
    public function __invoke(
        #[MapQueryString]
        AddressSearchFinderQueryString $queryString,
        Request $request,
    ): Response {
        $addressSearchFilter = new AddressSearch(
            limit: $queryString->limit,
            minScore: $queryString->minScore,
            filterByQuery: $queryString->query,
            filterByCountryCodes: null !== $queryString->countryCode ? [CountryCodeEnum::from($queryString->countryCode)] : null,
        );

        $results = $this->buildingAddressReader->searchPlaces($addressSearchFilter);

        $contentType = RequestContentTypeDecider::decideContentType($request, RequestContentTypeEnum::JSON);

        return match ($contentType) {
            RequestContentTypeEnum::WILDCARD,
            RequestContentTypeEnum::JSON => $this->resultsAsJson($results),
            RequestContentTypeEnum::CSV => $this->resultsAsCsv($results),
        };
    }

    /**
     * @param iterable<PlaceScored> $results
     */
    private function resultsAsJson(iterable $results): Response
    {
        return new StreamedJsonResponse([
            'hits' => $results,
        ]);
    }

    /**
     * @param iterable<PlaceScored> $results
     */
    private function resultsAsCsv(iterable $results): Response
    {
        $out = fopen('php://output', 'w');
        if (!\is_resource($out)) {
            throw new \UnexpectedValueException('Could not open output to write CSV');
        }

        return new StreamedResponse(
            static function () use ($results, $out): void {
                fputcsv($out, [
                    'score',
                    'identifier',
                    'country_code',
                    'address_id',
                    'building_id',
                    'entrance_id',
                    'address',
                    'latitude',
                    'longitude',
                ]);
                foreach ($results as $result) {
                    fputcsv($out, [
                        $result->score,
                        $result->place->identifier,
                        $result->place->postalAddress->addressCountry,
                        $result->place->additionalProperty->addressId,
                        $result->place->additionalProperty->buildingId,
                        $result->place->additionalProperty->entranceId,
                        (string) $result->place->postalAddress,
                        $result->place->geo?->latitude,
                        $result->place->geo?->longitude,
                    ]);
                }
            },
            Response::HTTP_OK,
            ['Content-Type' => 'text/csv'],
        );
    }
}
