<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Console;

use Symfony\Component\Console\Input\InputInterface;

final class ArgumentHelper
{
    private function __construct() {}

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enumName
     *
     * @return T
     *
     * @throws \UnexpectedValueException
     */
    public static function getStringBackedEnumArgument(InputInterface $input, string $argumentName, string $enumName): \BackedEnum
    {
        $value = $input->getArgument($argumentName);
        if (null === $value) {
            throw new \UnexpectedValueException("Error: {$argumentName} must be specified!");
        }

        if (!\is_string($value)) {
            throw new \UnexpectedValueException("Error: {$argumentName} must be a single string value!");
        }

        $enum = $enumName::tryFrom($value);
        if (!$enum) {
            $cases = array_column($enumName::cases(), 'value');

            throw new \UnexpectedValueException("Error: {$argumentName} must be one of: " . implode(', ', $cases));
        }

        return $enum;
    }
}
