<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Handler;

final class TasksResultsConditions
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $jobIdParam,
        /**
         * @var list<non-empty-string>
         */
        private array $buildingConditions = [],
    ) {}

    public function getSQLBuildingConditions(): string
    {
        return implode(' AND ', $this->buildingConditions);
    }

    /**
     * @param non-empty-string $condition
     */
    public function addBuildingConditions(string $condition): self
    {
        $this->buildingConditions[] = $condition;

        return $this;
    }
}
