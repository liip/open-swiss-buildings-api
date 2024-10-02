<?php

declare(strict_types=1);

namespace App\Application\Web\Controller;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Job\ResolverJob;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class ResolveJobController extends AbstractController
{
    public function __construct(
        private readonly ResolverJobReadRepositoryInterface $jobRepository,
    ) {}

    /**
     * Returns details about a resolver job.
     */
    #[Route('/resolve/jobs/{id}', methods: ['GET'])]
    #[OA\Response(response: '404', description: 'Resolver job not found')]
    #[OA\Response(response: '200', description: 'Resolver job information', content: new Model(type: ResolverJob::class))]
    public function __invoke(string $id): Response
    {
        try {
            $job = $this->jobRepository->getJobInfo(Uuid::fromString($id));
        } catch (\InvalidArgumentException|\UnexpectedValueException|ResolverJobNotFoundException) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($job, Response::HTTP_OK);
    }
}
