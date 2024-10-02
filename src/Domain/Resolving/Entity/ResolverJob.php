<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\Resolving\Model\Failure\ResolverJobFailure;
use App\Domain\Resolving\Model\Job\ResolverJob as ResolverJobModel;
use App\Domain\Resolving\Model\Job\ResolverJobStateEnum;
use App\Domain\Resolving\Model\Job\ResolverMetadata;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Domain\Resolving\Repository\DoctrineResolverJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type MetadataArray array<string, string|int>
 */
#[ORM\Entity(repositoryClass: DoctrineResolverJobRepository::class)]
class ResolverJob
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public readonly Uuid $id;

    #[ORM\Column]
    public readonly ResolverTypeEnum $type;

    /**
     * Raw input data for the job.
     *
     * @var resource
     */
    #[ORM\Column(type: Types::BLOB)]
    public $data;

    /**
     * Metadata or configuration about the resolver job.
     *
     * When creating a job, additional information can be put here,
     * which is then available when processing the job.
     *
     * @var MetadataArray
     */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata;

    #[ORM\Column]
    private ResolverJobStateEnum $state = ResolverJobStateEnum::CREATED;

    /**
     * @var array{type: string, details: string}|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $failure;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $modifiedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    /**
     * @param resource      $data
     * @param MetadataArray $metadata
     */
    public function __construct(
        ResolverTypeEnum $type,
        $data,
        array $metadata,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = Uuid::v7();
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt;

        $this->markAsCreated($createdAt);
    }

    public function getState(): ResolverJobStateEnum
    {
        return $this->state;
    }

    public function getFailure(): ?ResolverJobFailure
    {
        if (null === $this->failure) {
            return null;
        }

        return ResolverJobFailure::fromArray($this->failure);
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getModifiedAt(): \DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function markAsCreated(\DateTimeImmutable $now): void
    {
        $this->failure = null;
        $this->markAs(ResolverJobStateEnum::CREATED, $now);
    }

    public function markAsPreparing(\DateTimeImmutable $now): void
    {
        $this->markAs(ResolverJobStateEnum::PREPARING, $now);
    }

    public function markAsReady(\DateTimeImmutable $now): void
    {
        $this->failure = null;
        $this->markAs(ResolverJobStateEnum::READY, $now);
    }

    public function markAsResolving(\DateTimeImmutable $now): void
    {
        $this->markAs(ResolverJobStateEnum::RESOLVING, $now);
    }

    public function markAsCompleted(\DateTimeImmutable $now): void
    {
        $this->failure = null;
        $this->markAs(ResolverJobStateEnum::COMPLETED, $now);
    }

    public function markAsFailed(\DateTimeImmutable $now, ResolverJobFailure $failure): void
    {
        $this->failure = $failure->jsonSerialize();
        $this->markAs(ResolverJobStateEnum::FAILED, $now);
    }

    /**
     * Update the failure information, while keeping the current state unchanged.
     */
    public function flagAsTemporarilyFailed(\DateTimeImmutable $now, ResolverJobFailure $failure): void
    {
        $this->failure = $failure->jsonSerialize();
        $this->updateDates($now);
    }

    private function markAs(ResolverJobStateEnum $state, \DateTimeImmutable $now): void
    {
        $this->state = $state;
        $this->updateDates($now);
    }

    public function setMetadata(ResolverMetadata $metadata, \DateTimeImmutable $now): void
    {
        $this->metadata = $metadata->toArray();
        $this->updateDates($now);
    }

    private function updateDates(\DateTimeImmutable $now): void
    {
        $this->modifiedAt = $now;
        $this->expiresAt = $this->modifiedAt->add(new \DateInterval(ResolverJobModel::EXPIRES));
    }
}
