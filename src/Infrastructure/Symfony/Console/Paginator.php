<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Console;

use App\Infrastructure\Pagination;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Paginator
{
    private function __construct() {}

    /**
     * @param callable(Pagination $pagination): bool $fn Callable to run for each page, returning whether it can continue
     */
    public static function paginate(SymfonyStyle $io, Pagination $pagination, callable $fn): void
    {
        do {
            $continue = $fn($pagination);
            $pagination = $pagination->next();

            $loadMore = $continue && $io->confirm('Load more?', true);
        } while ($loadMore);
    }
}
