<?php

declare(strict_types=1);

namespace App\Application\Web;

use App\Application\Web\Contract\JobResultResponseCreatorInterface;
use App\Domain\Resolving\Contract\Result\ResolverResultReadRepositoryInterface;
use App\Domain\Resolving\Model\Job\ResolverJob;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\Uid\Uuid;

final readonly class JobResultJsonResponseCreator implements JobResultResponseCreatorInterface
{
    public function __construct(private ResolverResultReadRepositoryInterface $resultRepository) {}

    public function buildResponse(Uuid $jobId, ResolverJob $job): Response
    {
        return new StreamedJsonResponse([
            'results' => $this->resultRepository->getResults($jobId),
        ]);
    }
}
