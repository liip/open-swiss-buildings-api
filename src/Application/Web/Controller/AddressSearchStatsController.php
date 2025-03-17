<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Application\Contract\BuildingAddressSearcherInterface;
use App\Domain\AddressSearch\Model\SearchStats;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AddressSearchStatsController extends AbstractController
{
    public function __construct(
        private readonly BuildingAddressSearcherInterface $buildingAddressReader,
    ) {}

    /**
     * Get the status of the underlying search engine.
     */
    #[Route('/address-search/stats', methods: ['GET'])]
    #[OA\Response(
        response: '200',
        description: 'Status of the search engine',
        content: new Model(type: SearchStats::class),
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->buildingAddressReader->stats());
    }
}
