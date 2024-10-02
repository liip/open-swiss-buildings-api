<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Entity;

use App\Domain\Resolving\Repository\DoctrineResolverAddressRepository;
use App\Infrastructure\Address\Model\AddressFieldsTrait;
use App\Infrastructure\Address\Parser\StreetParser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineResolverAddressRepository::class)]
#[ORM\UniqueConstraint(name: 'address_matching_uniqueness', fields: ['job', 'uniqueHash'])]
#[ORM\Index(fields: ['streetName', 'streetHouseNumber', 'streetHouseNumberSuffix', 'postalCode', 'locality'], name: 'resolver_address_idx')]
#[ORM\Index(fields: ['streetNameNormalized', 'streetHouseNumber', 'streetHouseNumberSuffixNormalized', 'postalCode', 'localityNormalized'], name: 'resolver_address_normalized_idx')]
#[ORM\Index(fields: ['rangeType'], name: 'resolver_address_range_type_idx')]
class ResolverAddress
{
    use AddressFieldsTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public readonly Uuid $id;

    #[ORM\ManyToOne(targetEntity: ResolverJob::class)]
    #[ORM\JoinColumn(name: 'job_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private readonly ResolverJob $job;

    /**
     * Hash to identify unique addresses, and update/merge their metadata.
     */
    #[ORM\Column]
    public string $uniqueHash;

    #[ORM\Column(length: 4, nullable: true)]
    public ?string $rangeFrom = null;
    #[ORM\Column(length: 4, nullable: true)]
    public ?string $rangeTo = null;
    #[ORM\Column(length: 10, nullable: true)]
    public ?RangeAddressTypeEnum $rangeType = null;

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
     * @param list<array<string, string>> $additionalData
     */
    private function __construct(
        ResolverJob $job,
        string $street,
        string $postalCode,
        string $locality,
        array $additionalData,
    ) {
        if ('' === $street) {
            throw new \InvalidArgumentException('Street cannot be empty');
        }
        if ('' === $postalCode) {
            throw new \InvalidArgumentException('Postal code cannot be empty');
        }
        if ('' === $locality) {
            throw new \InvalidArgumentException('Locality cannot be empty');
        }

        $this->id = Uuid::v7();
        $this->job = $job;
        $this->postalCode = $postalCode;
        $this->locality = $locality;
        $this->additionalData = $additionalData;

        $s = StreetParser::createStreetFromString($street);

        $this->uniqueHash = hash('xxh3', implode(',', [(string) $s, $this->postalCode, $this->locality]));
    }
}
