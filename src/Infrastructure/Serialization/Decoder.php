<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

use App\Domain\Resolving\Model\AdditionalData;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type DecoderData array<string|int, mixed>
 *
 * @phpstan-import-type AdditionalDataAsArray from AdditionalData
 */
final readonly class Decoder
{
    private function __construct() {}

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return ($treatEmptyAsNull is true ? non-empty-string : string)
     *
     * @throws \UnexpectedValueException
     */
    public static function readString(array $data, string $property, bool $treatEmptyAsNull = false): string
    {
        $value = self::readOptionalString($data, $property, $treatEmptyAsNull);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return non-empty-string
     *
     * @throws \UnexpectedValueException
     */
    public static function readNonEmptyString(array $data, string $property): string
    {
        $value = self::readOptionalString($data, $property);
        if (null === $value || '' === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return numeric-string
     *
     * @throws \UnexpectedValueException
     */
    public static function readNumericString(array $data, string $property): string
    {
        $value = self::readNonEmptyString($data, $property);
        if (!is_numeric($value)) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be numeric, but value is \"{$value}\"");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return ($treatEmptyAsNull is true ? non-empty-string|null : string|null)
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalString(array $data, string $property, bool $treatEmptyAsNull = false): ?string
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new \UnexpectedValueException("Invalid value provided for property \"{$property}\", value is of type " . get_debug_type($value));
        }
        if ('' === $value && $treatEmptyAsNull) {
            return null;
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return non-empty-string|null
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalNonEmptyString(array $data, string $property, bool $treatEmptyAsNull = false): ?string
    {
        $value = self::readOptionalString($data, $property);
        if (null === $value) {
            return null;
        }
        if ('' === $value) {
            if ($treatEmptyAsNull) {
                return null;
            }
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be non-empty");
        }

        return $value;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param DecoderData      $data
     * @param non-empty-string $property
     * @param class-string<T>  $enumClass
     *
     * @return T
     *
     * @throws \UnexpectedValueException
     */
    public static function readBackedEnum(array $data, string $property, string $enumClass): \BackedEnum
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" is missing");
        }
        if ($value instanceof $enumClass) {
            return $value;
        }
        if (!\is_string($value)) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be a string");
        }

        $enumValue = $enumClass::tryFrom($value);
        if (null === $enumValue) {
            throw new \UnexpectedValueException("Invalid value \"{$value}\" provided for property \"{$property}\", value is \"{$value}\"");
        }

        return $enumValue;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @throws \UnexpectedValueException
     */
    public static function readInt(array $data, string $property): int
    {
        $value = self::readOptionalInt($data, $property);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalInt(array $data, string $property): ?int
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            return null;
        }
        if (\is_string($value) && is_numeric($value)) {
            $value = (int) $value;
        }
        if (!\is_int($value)) {
            throw new \UnexpectedValueException("Invalid value provided for property \"{$property}\", value is of type " . get_debug_type($value));
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return positive-int
     *
     * @throws \UnexpectedValueException
     */
    public static function readPositiveInt(array $data, string $property, bool $treatZeroAsNull = false): int
    {
        $value = self::readOptionalPositiveInt($data, $property, $treatZeroAsNull);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return positive-int|null
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalPositiveInt(array $data, string $property, bool $treatZeroAsNull = false): ?int
    {
        $value = self::readOptionalInt($data, $property);
        if (null === $value) {
            return null;
        }
        if (0 === $value && $treatZeroAsNull) {
            return null;
        }
        if ($value <= 0) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be a positive integer");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @throws \UnexpectedValueException
     */
    public static function readDateTime(array $data, string $property, string $format = Encoder::DATE_FORMAT): \DateTimeImmutable
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" is missing");
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if (!\is_string($value)) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be a string");
        }

        try {
            $dateTime = \DateTimeImmutable::createFromFormat($format, $value);
        } catch (\ValueError) {
            throw new \UnexpectedValueException("Invalid value \"{$value}\" provided for date-time property \"{$property}\"");
        }
        if (false === $dateTime) {
            throw new \UnexpectedValueException("Invalid value \"{$value}\" provided for date-time property \"{$property}\"");
        }

        return $dateTime;
    }

    /**
     * @template T of object
     *
     * @param DecoderData      $data
     * @param non-empty-string $property
     * @param class-string<T>  $objectClass
     *
     * @return T
     *
     * @throws \UnexpectedValueException
     */
    public static function readObject(array $data, string $property, string $objectClass): object
    {
        $object = self::readOptionalObject($data, $property, $objectClass);
        if (null === $object) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param DecoderData      $data
     * @param non-empty-string $property
     * @param class-string<T>  $objectClass
     *
     * @return T|null
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalObject(array $data, string $property, string $objectClass): ?object
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            return null;
        }
        if (!\is_array($value)) {
            throw new \UnexpectedValueException("Invalid value provided for property \"{$property}\", value is of type " . get_debug_type($value));
        }

        if (!method_exists($objectClass, 'fromArray')) {
            throw new \UnexpectedValueException("Class {$objectClass} doesn't have a method fromArray(), which is needed for the property {$property}");
        }

        try {
            return $objectClass::fromArray($value);
        } catch (\UnexpectedValueException $e) {
            throw new \UnexpectedValueException("Invalid value provided for object property \"{$property}\": {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @throws \UnexpectedValueException
     */
    public static function readUuid(array $data, string $property): Uuid
    {
        $value = self::readOptionalUuid($data, $property);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalUuid(array $data, string $property): ?Uuid
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            return null;
        }

        if (\is_string($value)) {
            try {
                $value = Uuid::fromString($value);
            } catch (\InvalidArgumentException) {
            }
        }
        if (!$value instanceof Uuid) {
            throw new \UnexpectedValueException("Invalid value provided for property \"{$property}\", value is of type " . get_debug_type($value));
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return non-empty-string|null
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalUuidAsString(array $data, string $property): ?string
    {
        $value = self::readOptionalUuid($data, $property);

        if (null === $value) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return list<AdditionalDataAsArray>
     *
     * @throws \UnexpectedValueException
     */
    public static function readAdditionalDataList(array $data, string $property): array
    {
        $array = self::readArray($data, $property);
        $result = [];

        foreach ($array as $additionalData) {
            if (!\is_array($additionalData)) {
                throw new \UnexpectedValueException("Property \"{$property}\" contains invalid entry of type " . get_debug_type($additionalData));
            }
            $resultEntry = [];
            foreach ($additionalData as $key => $value) {
                if (!\is_string($key)) {
                    throw new \UnexpectedValueException("Property \"{$property}\" contains invalid key \"{$key}\"");
                }
                if (!\is_string($value)) {
                    throw new \UnexpectedValueException("Property \"{$property}\" contains invalid value \"{$value}\"");
                }
                $resultEntry[$key] = $value;
            }

            $result[] = $resultEntry;
        }

        return $result;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return array<string|int, mixed>
     *
     * @throws \UnexpectedValueException
     */
    public static function readArray(array $data, string $property): array
    {
        $value = self::readOptionalArray($data, $property);
        if (null === $value) {
            throw new \UnexpectedValueException("Property \"{$property}\" needs to be present");
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     *
     * @return array<string|int, mixed>|null
     *
     * @throws \UnexpectedValueException
     */
    public static function readOptionalArray(array $data, string $property): ?array
    {
        $value = self::readValue($data, $property);
        if (null === $value) {
            return null;
        }
        if (\is_string($value)) {
            try {
                $value = json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
            }
        }
        if (!\is_array($value)) {
            throw new \UnexpectedValueException("Invalid value provided for property \"{$property}\", value is of type " . get_debug_type($value));
        }

        return $value;
    }

    /**
     * @param DecoderData      $data
     * @param non-empty-string $property
     */
    private static function readValue(array $data, string $property): mixed
    {
        if (\array_key_exists($property, $data)) {
            return $data[$property];
        }

        // Fall back to lowercase property
        $property = strtolower($property);
        if (\array_key_exists($property, $data)) {
            return $data[$property];
        }

        return null;
    }
}
