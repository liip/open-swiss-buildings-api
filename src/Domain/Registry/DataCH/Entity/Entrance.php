<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Entity;

use App\Domain\Registry\DataCH\Model\SwissLanguageEnum;
use App\Domain\Registry\DataCH\Repository\EntranceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'entrance')]
#[ORM\Entity(repositoryClass: EntranceRepository::class, readOnly: true)]
class Entrance
{
    /**
     * Federal Building identifier (numeric, 9chars)
     * length: 9.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'EGID', length: 9)]
    public string $EGID;

    /**
     * Federal Entrance identifier (numeric, 2chars)
     * length: 2.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'EDID', length: 2)]
    public string $EDID;

    /**
     * Federal Building-Address identifier (numeric, 9chars)
     * length: 9.
     */
    #[ORM\Column(name: 'EGAID', length: 9)]
    public string $EGAID;

    /**
     * Entrance number (alpha-numeric, 12chars)
     * length: 12.
     */
    #[ORM\Column(name: 'DEINR', length: 12)]
    public string $DEINR;

    /**
     * Federal Street identifier (alpha-numeric, 8chars)
     * length: 12.
     */
    #[ORM\Column(name: 'ESID', length: 8)]
    public string $ESID;

    /**
     * Street name (alpha-numeric, 60chars)
     * length: 60.
     */
    #[ORM\Column(name: 'STRNAME', length: 60)]
    public string $STRNAME;

    /**
     * Street name, abbreviation (alpha-numeric, 24chars)
     * length: 24.
     */
    #[ORM\Column(name: 'STRNAMK', length: 24)]
    public string $STRNAMK;

    /**
     * Street name, indexed (alpha-numeric, 3chars)
     * length: 3.
     */
    #[ORM\Column(name: 'STRINDX', length: 3)]
    public string $STRINDX;

    /**
     * Street name language (numeric, 4chars)
     * length: 4.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'STRSP', length: 4, enumType: SwissLanguageEnum::class)]
    public SwissLanguageEnum $STRSP;

    /**
     * Official name flag (numeric, 1chars)
     * length: 1.
     */
    #[ORM\Column(name: 'STROFFIZIEL')]
    public bool $STROFFIZIEL;

    /**
     * Zip code (numeric, 4chars)
     * length: 4.
     */
    #[ORM\Column(name: 'DPLZ4', length: 4)]
    public string $DPLZ4;

    /**
     * Zip code complement (numeric, 2chars)
     * length: 4.
     */
    #[ORM\Column(name: 'DPLZZ', length: 4)]
    public string $DPLZZ;

    /**
     * Location name (alpha-numeric, 40chars)
     * length: 60.
     */
    #[ORM\Column(name: 'DPLZNAME', length: 40, )]
    public string $DPLZNAME;

    /**
     * Coordinates: East (numeric, 11 chars)
     * length: 11.
     */
    #[ORM\Column(name: 'DKODE', length: 11)]
    public string $DKODE;

    /**
     * Coordinates: North (numeric, 11 chars)
     * length: 11.
     */
    #[ORM\Column(name: 'DKODN', length: 11)]
    public string $DKODN;

    /**
     * Official address flag (numeric, 1chars)
     * length: 1.
     */
    #[ORM\Column(name: 'DOFFADR')]
    public bool $DOFFADR;

    /**
     * Export date (alpha-numeric, 10 chars, yyyy-mm-dd)
     * length: 10.
     */
    #[ORM\Column(name: 'DEXPDAT', length: 10)]
    public \DateTimeImmutable $DEXPDAT;

    /**
     * The Building where it is located.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'EGID', referencedColumnName: 'EGID')]
    public ?Building $building = null;
}
