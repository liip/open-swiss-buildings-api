<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use App\Infrastructure\Meilisearch\Contract\IndexProviderInterface;
use App\Infrastructure\Meilisearch\Model\BuildingAddressEntity;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;

final readonly class AddressIndexConfigurator implements IndexProviderInterface
{
    public function __construct(
        private Client $client,
    ) {}

    public function pruneBuildingAddressIndex(): void
    {
        $this->getBuildingEntranceIndex()->deleteAllDocuments();
    }

    public function dropBuildingAddressIndex(): void
    {
        $this->getBuildingEntranceIndex()->delete();
    }

    public function configureBuildingAddressIndex(): void
    {
        $index = $this->getBuildingEntranceIndex();
        $index->updateSettings([
            'filterableAttributes' => BuildingAddressEntity::FILTERABLE_FIELDS,
            'searchableAttributes' => BuildingAddressEntity::SEARCHABLE_FIELDS,
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'exactness',
                'attribute',
                'sort',
            ],
            'typoTolerance' => [
                'minWordSizeForTypos' => [
                    'oneTypo' => 4,
                    'twoTypos' => 6,
                ],
            ],
        ]);
    }

    public function getBuildingEntranceIndex(): Indexes
    {
        return $this->client->index(BuildingAddressEntity::INDEX_NAME);
    }
}
