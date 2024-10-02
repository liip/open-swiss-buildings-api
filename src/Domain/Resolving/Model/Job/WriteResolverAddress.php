<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Job;

use App\Domain\Resolving\Entity\RangeAddressTypeEnum;
use App\Domain\Resolving\Model\AdditionalData;
use App\Infrastructure\Address\Model\StreetNumber;
use App\Infrastructure\Address\Model\StreetNumberInterface;
use App\Infrastructure\Address\Model\StreetNumberRange;
use App\Infrastructure\Address\Model\StreetNumberSuffixRange;
use App\Infrastructure\Address\Parser\StreetParser;
use Symfony\Component\Uid\Uuid;

final class WriteResolverAddress
{
    private Uuid $id;
    private Uuid $jobId;

    /**
     * Hash to identify unique addresses, and update/merge their metadata.
     */
    private string $uniqueHash;

    /**
     * @var non-empty-string|null
     */
    private ?string $street = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $streetName = null;

    private ?int $houseNumber = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $houseNumberSuffix = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $rangeFrom = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $rangeTo = null;

    private ?RangeAddressTypeEnum $rangeType = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $postalCode;

    /**
     * @var non-empty-string|null
     */
    private ?string $locality;

    private AdditionalData $additionalData;

    public function __construct(
        Uuid $jobId,
        string $street,
        string $postalCode,
        string $locality,
        AdditionalData $additionalData,
    ) {
        $this->id = Uuid::v7();
        $this->jobId = $jobId;
        $this->postalCode = $postalCode ?: null;
        $this->locality = $locality ?: null;
        $this->additionalData = $additionalData;

        if (null !== $this->postalCode && mb_strlen($this->postalCode) > 4) {
            throw new \InvalidArgumentException("Postal code can only be 4 characters long, \"{$this->postalCode}\" given");
        }

        if ('' === $street) {
            $this->uniqueHash = hash('xxh3', implode(',', [$this->postalCode, $this->locality]));

            return;
        }

        $this->street = $street;
        $this->uniqueHash = hash('xxh3', implode(',', [$this->street, $this->postalCode, $this->locality]));

        if (null === $parsedStreet = StreetParser::createStreetFromString($street)) {
            throw new \InvalidArgumentException('Invalid ');
        }
        $this->streetName = $parsedStreet->streetName;

        $number = $parsedStreet->number;
        if (!$number instanceof StreetNumberInterface) {
            return;
        }

        switch ($number::class) {
            case StreetNumberSuffixRange::class:
                $this->rangeFrom = $number->suffixFrom;
                $this->rangeTo = $number->suffixTo;
                $this->rangeType = RangeAddressTypeEnum::HOUSE_NUMBER_SUFFIX;
                $this->houseNumber = $number->number;
                break;
            case StreetNumberRange::class:
                $this->rangeFrom = (string) $number->numberFrom;
                $this->rangeTo = (string) $number->numberTo;
                $this->rangeType = RangeAddressTypeEnum::HOUSE_NUMBER;
                break;
            case StreetNumber::class:
                $this->houseNumber = $number->number;
                $this->houseNumberSuffix = $number->suffix;
                break;
        }
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function jobId(): Uuid
    {
        return $this->jobId;
    }

    public function uniqueHash(): string
    {
        return $this->uniqueHash;
    }

    public function street(): ?string
    {
        return $this->street;
    }

    public function streetName(): ?string
    {
        return $this->streetName;
    }

    public function houseNumber(): ?int
    {
        return $this->houseNumber;
    }

    public function houseNumberSuffix(): ?string
    {
        return $this->houseNumberSuffix;
    }

    public function rangeFrom(): ?string
    {
        return $this->rangeFrom;
    }

    public function rangeTo(): ?string
    {
        return $this->rangeTo;
    }

    public function rangeType(): ?RangeAddressTypeEnum
    {
        return $this->rangeType;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function locality(): ?string
    {
        return $this->locality;
    }

    public function additionalData(): AdditionalData
    {
        return $this->additionalData;
    }
}
