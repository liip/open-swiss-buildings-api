<?php

declare(strict_types=1);

namespace App\Infrastructure\Address;

use Composer\Pcre\Preg;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class AddressNormalizer
{
    private const string SEPARATOR = '';

    private const string LOCALE = 'de_CH';

    public function __construct(
        private SluggerInterface $slugger,
    ) {}

    public function normalize(string $value): string
    {
        return (string) $this->slugger->slug($value, self::SEPARATOR, self::LOCALE)->folded();
    }

    public function normalizeLocality(string $locality, string $municipality, string $cantonCode): string
    {
        $replacements = [
            "| {$cantonCode}$|",
            "| \\({$cantonCode}\\)$|",
            "| \\({$municipality}\\)$|",
            "| \\({$municipality} {$cantonCode}\\)$|",
            "| \\({$municipality} \\({$cantonCode}\\)\\)$|",
        ];

        $locality = Preg::replace($replacements, '', $locality);

        return $this->normalize($locality);
    }
}
