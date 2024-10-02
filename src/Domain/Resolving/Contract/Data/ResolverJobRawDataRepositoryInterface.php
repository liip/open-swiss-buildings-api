<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Contract\Data;

use App\Domain\Resolving\Exception\ResolverJobNotFoundException;
use App\Domain\Resolving\Model\Data\ResolverJobRawData;
use Symfony\Component\Uid\Uuid;

interface ResolverJobRawDataRepositoryInterface
{
    /**
     * Returns the data of the resolver job with the given ID.
     *
     * @throws ResolverJobNotFoundException
     */
    public function getRawData(Uuid $id): ResolverJobRawData;
}
