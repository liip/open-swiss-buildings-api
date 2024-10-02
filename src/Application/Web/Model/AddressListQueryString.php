<?php

declare(strict_types=1);

namespace App\Application\Web\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AddressListQueryString
{
    public const int DEFAULT_LIMIT = 25;
    public const int DEFAULT_OFFSET = 0;

    /**
     * Number of addresses to fetch.
     *
     * @var int<1, 2000>
     */
    #[Assert\GreaterThanOrEqual(1)]
    #[Assert\LessThanOrEqual(2000)]
    public int $limit = self::DEFAULT_LIMIT;

    /**
     * Number of addresses to skip from the whole total.
     *
     * @var int<0, max>
     */
    #[Assert\GreaterThanOrEqual(0)]
    public int $offset = self::DEFAULT_OFFSET;
}
