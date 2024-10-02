<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Contract\BuildingAddressFinderInterface;
use App\Infrastructure\SchemaOrg\Place;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\UuidV7;

final class AddressesGetController extends AbstractController
{
    public function __construct(
        private readonly BuildingAddressFinderInterface $buildingAddressFinder,
    ) {}

    /**
     * Returns details about a single address.
     *
     * The response model corresponds to [https://schema.org/Place](https://schema.org/Place).
     */
    #[Route('/addresses/{id}', methods: ['GET'])]
    #[OA\Response(
        response: '200',
        description: 'Returns the found address',
        content: new Model(type: Place::class),
    )]
    #[OA\Response(response: '404', description: 'Address not found')]
    public function __invoke(string $id): Response
    {
        try {
            $addressId = new UuidV7($id);
        } catch (\InvalidArgumentException) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $place = $this->buildingAddressFinder->findPlace($addressId);

        if (null === $place) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($place);
    }
}
