<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\Resolving\Repository\DoctrineResolverAddressMatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineResolverAddressMatchRepository::class)]
#[ORM\Index(fields: ['matchingBuildingId', 'matchingEntranceId'], name: 'resolver_address_match_idx')]
class ResolverAddressMatch
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public Uuid $id;

    #[ORM\ManyToOne(targetEntity: ResolverAddress::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ResolverAddress $address;

    #[ORM\Column]
    private int $confidence;

    #[ORM\Column(nullable: true)]
    public ?string $matchingBuildingId;

    #[ORM\Column(nullable: true)]
    public ?string $matchingEntranceId;

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
     */
    private function __construct(
        ResolverAddress $address,
        int $confidence,
        string $matchType,
        array $additionalData,
        ?string $matchingBuildingId = null,
        ?string $matchingEntranceId = null,
    ) {
        $this->id = Uuid::v7();
        $this->address = $address;
        $this->confidence = $confidence;
        $this->matchType = $matchType;
        $this->matchingBuildingId = $matchingBuildingId;
        $this->matchingEntranceId = $matchingEntranceId;
        $this->additionalData = $additionalData;
    }
}
