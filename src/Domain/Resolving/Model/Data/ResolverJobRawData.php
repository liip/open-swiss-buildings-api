<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Data;

use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type ResolverMetadataAsArray from ResolverMetadata
 */
final class ResolverJobRawData
{
    public readonly ResolverMetadata $metadata;

    /**
     * @param ResolverMetadata|ResolverMetadataAsArray $metadata needs to support an array when coming from source data
     */
    public function __construct(
        public readonly Uuid $id,
        public readonly ResolverTypeEnum $type,
        /**
         * @var resource
         */
        private $resource,
        ResolverMetadata|array $metadata,
    ) {
        if (\is_array($metadata)) {
            $metadata = ResolverMetadata::fromArray($metadata);
        }
        $this->metadata = $metadata;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }
}
