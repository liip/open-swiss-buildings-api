<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Contract;

use Meilisearch\Endpoints\Indexes;

interface IndexProviderInterface
{
    public function getBuildingEntranceIndex(): Indexes;
}
