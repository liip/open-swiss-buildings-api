<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Serialization\Decoder;
use App\Infrastructure\Serialization\Encoder;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type ResolverJobAsArray array{
 *    id: string,
 *    type: non-empty-string,
 *    metadata: ResolverMetadata,
 *    state: non-empty-string,
 *    failure: ResolverJobFailure|null,
 *    created_at: non-falsy-string,
 *    modified_at: non-falsy-string,
 *    expires_at: non-falsy-string,
 *  }
 */
final readonly class ResolverJob implements \JsonSerializable, \Stringable
{
    public const string EXPIRES = 'P2D';

    #[OA\Property(format: 'uuid')]
    public string $id;

    public ResolverTypeEnum $type;

    /**
     * Metadata or configuration about the resolver job.
     *
     * When creating a job, additional information can be put here,
     * which is then available when processing the job.
     */
    public ResolverMetadata $metadata;

    public ResolverJobStateEnum $state;

    public ?ResolverJobFailure $failure;

    #[OA\Property(property: 'created_at')]
    public \DateTimeInterface $createdAt;

    #[OA\Property(property: 'modified_at')]
    public \DateTimeInterface $modifiedAt;

    #[OA\Property(property: 'expires_at')]
    public \DateTimeInterface $expiresAt;

    /**
     * @param ResolverJobFailure|array{type: string, details: string}|null                                                            $failure  Optional failure data, can be an array when coming from the data source
     * @param ResolverMetadata|array{additional-columns?: string, csv-delimiter?: non-empty-string, csv-enclosure?: non-empty-string} $metadata Metadata, can be an array when coming from the data source
     */
    public function __construct(
        string $id,
        ResolverTypeEnum $type,
        ResolverMetadata|array $metadata,
        ResolverJobStateEnum $state,
        ResolverJobFailure|array|null $failure,
        \DateTimeInterface $createdAt,
        \DateTimeInterface $modifiedAt,
        \DateTimeInterface $expiresAt,
    ) {
        $this->id = $id;
        $this->type = $type;
        if (\is_array($metadata)) {
            $metadata = ResolverMetadata::fromArray($metadata);
        }
        $this->metadata = $metadata;
        $this->state = $state;
        if (\is_array($failure)) {
            $failure = ResolverJobFailure::fromArray($failure);
        }
        $this->failure = $failure;
        $this->createdAt = $createdAt;
        $this->modifiedAt = $modifiedAt;
        $this->expiresAt = $expiresAt;
    }

    public static function create(
        Uuid $id,
        ResolverTypeEnum $type,
        ResolverMetadata $metadata,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            (string) $id,
            $type,
            $metadata,
            ResolverJobStateEnum::CREATED,
            null,
            $createdAt,
            $createdAt,
            $createdAt->add(new \DateInterval(self::EXPIRES)),
        );
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Decoder::readString($data, 'id'),
            Decoder::readBackedEnum($data, 'type', ResolverTypeEnum::class),
            Decoder::readObject($data, 'metadata', ResolverMetadata::class),
            Decoder::readBackedEnum($data, 'state', ResolverJobStateEnum::class),
            Decoder::readOptionalObject($data, 'failure', ResolverJobFailure::class),
            Decoder::readDateTime($data, 'created_at'),
            Decoder::readDateTime($data, 'modified_at'),
            Decoder::readDateTime($data, 'expires_at'),
        );
    }

    /**
     * @return ResolverJobAsArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'metadata' => $this->metadata,
            'state' => $this->state->value,
            'failure' => $this->failure,
            'created_at' => $this->createdAt->format(Encoder::DATE_FORMAT),
            'modified_at' => $this->modifiedAt->format(Encoder::DATE_FORMAT),
            'expires_at' => $this->expiresAt->format(Encoder::DATE_FORMAT),
        ];
    }

    public function __toString(): string
    {
        return "{$this->id} ({$this->type->value})";
    }

    #[Serializer\Ignore]
    public function getIdentifier(): ResolverJobIdentifier
    {
        return new ResolverJobIdentifier(Uuid::fromString($this->id), $this->type);
    }

    /**
     * Returns true if the job was just created and ready for preparation.
     */
    #[Serializer\Ignore]
    public function isReadyForPreparation(): bool
    {
        return ResolverJobStateEnum::CREATED === $this->state;
    }

    /**
     * Returns true if the job is ready for resolving.
     */
    #[Serializer\Ignore]
    public function isReadyForResolving(): bool
    {
        return ResolverJobStateEnum::READY === $this->state;
    }

    /**
     * Returns true if the job is in preparation state or already completed.
     */
    #[Serializer\Ignore]
    public function isPrepared(): bool
    {
        return ResolverJobStateEnum::PREPARING === $this->state
            || ResolverJobStateEnum::READY === $this->state
            || ResolverJobStateEnum::RESOLVING === $this->state
            || ResolverJobStateEnum::COMPLETED === $this->state
            || ResolverJobStateEnum::FAILED === $this->state;
    }

    /**
     * Returns true if the job is in resolving state or already completed.
     */
    #[Serializer\Ignore]
    public function isResolved(): bool
    {
        return ResolverJobStateEnum::COMPLETED === $this->state;
    }

    /**
     * Returns true if the job is in failed state.
     */
    #[Serializer\Ignore]
    public function isFailed(): bool
    {
        return ResolverJobStateEnum::FAILED === $this->state;
    }
}
