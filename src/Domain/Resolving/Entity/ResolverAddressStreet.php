<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\Resolving\Repository\DoctrineResolverAddressStreetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineResolverAddressStreetRepository::class)]
#[ORM\Index(fields: ['address', 'streetId'], name: 'resolver_address_street_idx')]
class ResolverAddressStreet
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: ResolverAddress::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ResolverAddress $address;

    #[ORM\Id]
    #[ORM\Column]
    public string $streetId;

    #[ORM\Column]
    private int $confidence;

    #[ORM\Column]
    public string $matchType;

    /**
     * @param int<0, 100> $confidence
     */
    private function __construct(
        ResolverAddress $address,
        string $streetId,
        int $confidence,
        string $matchType,
    ) {
        $this->address = $address;
        $this->streetId = $streetId;
        $this->confidence = $confidence;
        $this->matchType = $matchType;
    }
}
