<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Repository\DoctrineResolverResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineResolverResultRepository::class)]
#[ORM\Index(fields: ['jobId'], name: 'resolver_result_job_id')]
#[ORM\UniqueConstraint(name: 'resolver_result_entry', fields: ['jobId', 'countryCode', 'buildingEntranceId'])]
class ResolverResult
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public readonly Uuid $id;

    #[ORM\Column(type: UuidType::NAME)]
    public readonly Uuid $jobId;

    #[ORM\ManyToOne(targetEntity: ResolverJob::class)]
    #[ORM\JoinColumn(name: 'job_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private readonly ResolverJob $job;

    #[ORM\Column]
    private int $confidence;

    #[ORM\Column(nullable: true, length: 2)]
    public ?string $countryCode;

    #[ORM\Column(nullable: true)]
    public ?string $buildingId;

    #[ORM\Column(nullable: true)]
    public ?string $entranceId;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    public readonly ?Uuid $buildingEntranceId;

    #[ORM\ManyToOne(targetEntity: BuildingEntrance::class)]
    #[ORM\JoinColumn(name: 'building_entrance_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private readonly ?BuildingEntrance $buildingEntrance;

    #[ORM\Column]
    public string $matchType;

    /**
     * Additional data/columns of the result, copied from the input data.
     *
     * @var list<array<string, string>>
     */
    #[ORM\Column(type: Types::JSON)]
    public readonly array $additionalData;

    /**
     * @param int<0, 100>                 $confidence
     * @param list<array<string, string>> $additionalData
     */
    private function __construct(
        ResolverJob $job,
        int $confidence,
        string $matchType,
        Uuid $buildingEntranceId,
        array $additionalData,
    ) {
        $this->id = Uuid::v7();
        $this->jobId = $job->id;
        $this->job = $job;
        $this->confidence = $confidence;
        $this->matchType = $matchType;
        $this->buildingEntranceId = $buildingEntranceId;
        $this->buildingEntrance = null;
        $this->additionalData = $additionalData;
    }
}
