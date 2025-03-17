<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler;

use App\Domain\Resolving\Contract\Job\ResolverJobReadRepositoryInterface;
use App\Domain\Resolving\Contract\Job\TaskResolverInterface;
use App\Domain\Resolving\Exception\ResolverJobFailedException;
use App\Domain\Resolving\Model\Job\ResolverJobIdentifier;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

abstract readonly class AbstractDatabaseResolver implements TaskResolverInterface
{
    public function __construct(
        private ResolverJobReadRepositoryInterface $jobRepository,
        protected EntityManagerInterface $entityManager,
    ) {}

    abstract public function canResolveTasks(ResolverTypeEnum $type): bool;

    abstract protected function buildResultInsertSQL(TasksResultsConditions $conditions): string;

    protected function resolveTasksWithFiltering(ResolverJobIdentifier $job): void
    {
        $conditions = new TasksResultsConditions(jobIdParam: ':jobId');
        $params = ['jobId' => $job->id];

        try {
            $jobMetadata = $this->jobRepository->getJobInfo($job->id);
            if (null !== $country = $jobMetadata->metadata->filterByCountry) {
                $conditions->addBuildingConditions('b.country_code = :buildingCountry');
                $params['buildingCountry'] = $country->value;
            }

            $this->entityManager->getConnection()->executeStatement($this->buildResultInsertSQL($conditions), $params);
        } catch (\Throwable $e) {
            throw ResolverJobFailedException::wrap($e);
        }
    }
}
