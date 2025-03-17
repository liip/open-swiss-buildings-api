<?php

declare(strict_types=1);

namespace App\Domain\Bridge;

use App\Application\Messaging\Message\AddressSearchIndexUpdatedAfterMessage;
use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\AddressSearch\Contract\BuildingAddressBridgedFactoryInterface;
use App\Domain\AddressSearch\Exception\BuildingAddressNotFoundException;
use App\Domain\AddressSearch\Model\Address;
use App\Domain\AddressSearch\Model\BuildingAddress;
use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenImported;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenPruned;
use App\Domain\BuildingData\Model\BuildingEntrance;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AddressSearchToBuildingDataBridge implements BuildingAddressBridgedFactoryInterface
{
    public function __construct(
        private BuildingEntranceReadRepositoryInterface $buildingEntranceRepository,
        private AddressSearchWriteRepositoryInterface $addressSearchRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        #[Autowire(param: 'app.enable_search_engine')]
        private bool $enableIndexing = true,
    ) {}

    #[AsEventListener]
    public function onBuildingEntrancesImported(BuildingEntrancesHaveBeenImported $event): void
    {
        if ($this->enableIndexing) {
            $this->messageBus->dispatch(new AddressSearchIndexUpdatedAfterMessage($event->timestamp));
        }
    }

    #[AsEventListener]
    public function onBuildingEntrancesDeleted(BuildingEntrancesHaveBeenPruned $event): void
    {
        $this->logger->info('Deleting documents from the search index, not imported since {date}', [
            'date' => $event->importedAtBefore->format(\DateTimeInterface::ATOM),
        ]);

        $this->addressSearchRepository->deleteByImportedAtBefore($event->importedAtBefore);
    }

    public function countBuildingAddresses(): int
    {
        return $this->buildingEntranceRepository->countBuildingEntrances();
    }

    public function getBuildingAddresses(): iterable
    {
        foreach ($this->buildingEntranceRepository->getBuildingEntrances() as $buildingEntrance) {
            yield $this->createBuildingAddressFromBuildingEntrance($buildingEntrance);
        }
    }

    public function getBuildingAddressesImportedSince(\DateTimeImmutable $timestamp): iterable
    {
        foreach ($this->buildingEntranceRepository->getBuildingEntrancesImportedSince($timestamp) as $buildingEntrance) {
            yield $this->createBuildingAddressFromBuildingEntrance($buildingEntrance);
        }
    }

    public function getBuildingAddress(Uuid $id): BuildingAddress
    {
        $buildingEntrance = $this->buildingEntranceRepository->findBuildingEntrance($id);
        if (null === $buildingEntrance) {
            throw new BuildingAddressNotFoundException($id);
        }

        return $this->createBuildingAddressFromBuildingEntrance($buildingEntrance);
    }

    private function createBuildingAddressFromBuildingEntrance(BuildingEntrance $buildingEntrance): BuildingAddress
    {
        $address = $this->createAddressFromBuildingEntrance($buildingEntrance);

        if ('' === $buildingEntrance->buildingId) {
            throw new \InvalidArgumentException('Building ID is empty');
        }
        if ('' === $buildingEntrance->addressId) {
            throw new \InvalidArgumentException('Address ID is empty');
        }
        if ('' === $buildingEntrance->entranceId) {
            throw new \InvalidArgumentException('Entrance ID is empty');
        }

        return new BuildingAddress(
            id: BuildingAddress::extractIdentifier($buildingEntrance->id),
            buildingId: $buildingEntrance->buildingId,
            addressId: $buildingEntrance->addressId,
            entranceId: $buildingEntrance->entranceId,
            streetId: '' !== $buildingEntrance->streetId ? $buildingEntrance->streetId : null,
            language: $buildingEntrance->streetNameLanguage->value,
            address: $address,
            coordinates: $buildingEntrance->coordinates,
            importedAt: (int) $buildingEntrance->importedAt->format('U'),
        );
    }

    private function createAddressFromBuildingEntrance(BuildingEntrance $buildingEntrance): Address
    {
        return new Address(
            streetName: $buildingEntrance->street->streetName ?? '',
            streetNameAbbreviation: $buildingEntrance->streetAbbreviated->streetName ?? '',
            streetHouseNumber: (string) ($buildingEntrance->street->number ?? ''),
            postalCode: $buildingEntrance->postalCode,
            locality: $buildingEntrance->locality,
            municipality: $buildingEntrance->municipality,
            municipalityCode: $buildingEntrance->municipalityCode,
            countryCode: $buildingEntrance->countryCode->value,
        );
    }
}
