<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\Resolving\Repository\DoctrineResolverTaskRepository;
use App\Infrastructure\PostGis\SRIDEnum;
use App\Infrastructure\PostGis\Types\GeoJsonType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineResolverTaskRepository::class)]
#[ORM\UniqueConstraint(name: 'task_matching_uniqueness', fields: ['job', 'matchingUniqueHash'])]
#[ORM\Index(name: 'building_entrance_matching_hash_idx', fields: ['matchingUniqueHash'])]
#[ORM\Index(name: 'building_entrance_building_id_idx', fields: ['matchingBuildingId'])]
#[ORM\Index(name: 'building_entrance_municipality_code_idx', fields: ['matchingMunicipalityCode'])]
class ResolverTask
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public readonly Uuid $id;

    #[ORM\ManyToOne(targetEntity: ResolverJob::class)]
    #[ORM\JoinColumn(name: 'job_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private readonly ResolverJob $job;

    /**
     * Hash to identify unique jobs, given their "matching" property.
     * This is used to identify duplicated Tasks, and update/merge their metadata.
     *
     * This value is computed at storing time, by the repository.
     */
    #[ORM\Column]
    public string $matchingUniqueHash;

    #[ORM\Column(nullable: true)]
    public ?string $matchingBuildingId;

    #[ORM\Column(nullable: true)]
    public ?string $matchingMunicipalityCode;

    #[ORM\Column(nullable: true)]
    public ?string $matchingEntranceId;

    /**
     * Geo-Coordinates in CH1903+/LV95 system
     * See: https://epsg.io/2056.
     */
    #[ORM\Column(
        type: GeoJsonType::NAME,
        nullable: true,
        options: ['geometry_type' => 'GEOMETRY', 'srid' => SRIDEnum::WGS84->value],
    )]
    public ?GeoJsonType $matchingGeoJson;

    #[ORM\Column]
    private int $confidence;

    #[ORM\Column]
    public string $matchType;

    /**
     * A list of additional data/columns of the data entry.
     *
     * Each data entry can have additional data, which gets
     * appended to the result while resolving.
     *
     * Internally, this is a list of additional data, which
     * has multiple entries in case the same entry exists
     * multiple times in the input data. The additional data
     * will be merged while resolving.
     *
     * @var list<array<string, string>>
     */
    #[ORM\Column(type: Types::JSON)]
    public readonly array $additionalData;

    /**
     * @param int<0, 100>                 $confidence
     * @param list<array<string, string>> $additionalData
     * @param non-empty-string|null       $matchingBuildingId
     * @param non-empty-string|null       $matchingMunicipalityCode
     * @param non-empty-string|null       $matchingEntranceId
     */
    private function __construct(
        ResolverJob $job,
        int $confidence,
        string $matchType,
        array $additionalData,
        ?string $matchingBuildingId = null,
        ?string $matchingMunicipalityCode = null,
        ?string $matchingEntranceId = null,
    ) {
        $this->id = Uuid::v7();
        $this->job = $job;
        $this->confidence = $confidence;
        $this->matchType = $matchType;
        $this->matchingBuildingId = $matchingBuildingId;
        $this->matchingMunicipalityCode = $matchingMunicipalityCode;
        $this->matchingEntranceId = $matchingEntranceId;
        $this->additionalData = $additionalData;
    }
}
