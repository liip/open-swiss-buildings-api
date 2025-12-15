<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Console;

use BackedEnum;
use Symfony\Component\Console\Input\InputInterface;

final class OptionHelper
{
    private function __construct() {}

    /**
     * @param ?string[] $values
     *
     * @return non-empty-list<non-empty-string>|null
     */
    public static function getStringListOptionValues(?array $values): ?array
    {
        if (null === $values || [] === $values) {
            return null;
        }

        $result = [];
        foreach ($values as $value) {
            if (!\is_string($value) || '' === $value) {
                throw new \UnexpectedValueException('Each option must be a non-empty string!');
            }
            $result[] = $value;
        }

        return $result;
    }

    /**
     * @return non-empty-list<non-empty-string>|null
     */
    public static function getStringListOptionValuesFromInput(InputInterface $input, string $optionName): ?array
    {
        return self::getStringListOptionValues($input->getOption($optionName));
    }

    /**
     * @template T of BackedEnum
     *
     * @param ?string[]       $values
     * @param class-string<T> $enumName
     *
     * @return non-empty-list<T>|null
     */
    public static function getStringBackedEnumListOptionValues(?array $values, string $enumName): ?array
    {
        if (null === $values || [] === $values) {
            return null;
        }

        $enums = [];
        foreach ($values as $value) {
            $enums[] = $enumName::from($value);
        }

        return $enums;
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumName
     *
     * @return non-empty-list<T>|null
     */
    public static function getStringBackedEnumListOptionValuesFromInput(InputInterface $input, string $optionName, string $enumName): ?array
    {
        return self::getStringBackedEnumListOptionValues(self::getStringListOptionValuesFromInput($input, $optionName), $enumName);
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumName
     *
     * @return T|null
     */
    public static function getStringBackedEnumOptionValue(InputInterface $input, string $optionName, string $enumName): ?\BackedEnum
    {
        $value = $input->getOption($optionName);
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new \UnexpectedValueException("Error: {$optionName} must be a single string value!");
        }

        return $enumName::from($value);
    }

    /**
     * @param positive-int      $minValue
     * @param positive-int|null $maxValue
     *
     * @return positive-int|null
     */
    public static function getPositiveIntOptionValue(InputInterface $input, mixed $optionName, int $minValue = 1, ?int $maxValue = null): ?int
    {
        $value = $input->getOption($optionName);
        if (null === $value) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new \UnexpectedValueException("{$optionName} has to be a number");
        }

        $value = (int) $value;
        if ($value < $minValue) {
            throw new \UnexpectedValueException("{$optionName} has to be a number greater or equals to {$minValue}");
        }

        if (null !== $maxValue && $value > $maxValue) {
            throw new \UnexpectedValueException("{$optionName} has to be a number less than {$maxValue}");
        }

        return $value;
    }
}
