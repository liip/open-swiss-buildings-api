<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PingController extends AbstractController
{
    /**
     * Check availability of the application.
     */
    #[Route('/ping', methods: ['GET'])]
    #[OA\Response(response: '204', description: 'Successful ping')]
    public function index(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
