<?php

declare(strict_types=1);

namespace App\Application\Web\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AddressSearchFinderQueryString
{
    /**
     * @var non-empty-string
     */
    #[Assert\NotBlank]
    public string $query;

    /**
     * @var int<1, 50>
     */
    #[Assert\GreaterThanOrEqual(1)]
    #[Assert\LessThanOrEqual(50)]
    public int $limit = 10;

    /**
     * @var int<1, 100>|null
     */
    #[Assert\GreaterThanOrEqual(1)]
    #[Assert\LessThanOrEqual(100)]
    public ?int $minScore = null;
}
