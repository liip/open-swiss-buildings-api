<?php

declare(strict_types=1);

namespace App\Application\Web\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class AddressSearchFinderQueryString
{
    /**
     * @var non-empty-string
     */
    #[Assert\NotBlank]
    #[OA\Property(description: 'Search query', example: 'Affoltern')]
    public string $query;

    /**
     * @var int<1, 50>
     */
    #[Assert\GreaterThanOrEqual(1)]
    #[Assert\LessThanOrEqual(50)]
    #[OA\Property(
        description: 'Number of results to return',
        maximum: 50,
        minimum: 1,
        example: 25,
    )]
    public int $limit = 10;

    /**
     * @var int<1, 100>|null
     */
    #[Assert\GreaterThanOrEqual(1)]
    #[Assert\LessThanOrEqual(100)]
    #[OA\Property(
        description: 'Lowest matching score of results to return',
        maximum: 100,
        minimum: 1,
        example: 90,
    )]
    public ?int $minScore = null;
}
