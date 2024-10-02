<?php

declare(strict_types=1);

namespace App\Application\Messaging\MessageHandler;

use App\Application\Contract\BuildingAddressIndexerInterface;
use App\Application\Messaging\Message\AddressSearchIndexUpdatedAfterMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddressSearchIndexMessageHandler
{
    public function __construct(
        private BuildingAddressIndexerInterface $addressIndexer,
    ) {}

    public function __invoke(AddressSearchIndexUpdatedAfterMessage $message): void
    {
        foreach ($this->addressIndexer->indexBuildingAddressesImportedSince($message->timestamp) as $result) {
            // Loop for indexing to happen
        }
    }
}
