<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use Meilisearch\Client;
use Nyholm\Dsn\DsnParser;

final class MeilisearchClientFactory
{
    public static function fromDsn(string $dsnString): Client
    {
        $dsn = DsnParser::parse($dsnString);
        $apiToken = $dsn->getParameter('apiKey');
        $dsn = $dsn
            ->withScheme($dsn->getScheme() ?? 'http')
            ->withoutParameter('apiKey')
        ;

        return new Client((string) $dsn, $apiToken);
    }
}
