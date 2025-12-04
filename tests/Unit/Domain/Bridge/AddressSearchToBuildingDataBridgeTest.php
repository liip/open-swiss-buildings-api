<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Bridge;

use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\Bridge\AddressSearchToBuildingDataBridge;
use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenPruned;
use App\Infrastructure\Model\CountryCodeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;

#[Small]
#[CoversClass(AddressSearchToBuildingDataBridge::class)]
final class AddressSearchToBuildingDataBridgeTest extends TestCase
{
    private AddressSearchWriteRepositoryInterface&MockObject $addressSearchRepository;

    private MessageBusInterface&MockObject $messageBus;

    private AddressSearchToBuildingDataBridge $bridge;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->addressSearchRepository = $this->createMock(AddressSearchWriteRepositoryInterface::class);
        $buildingEntranceRepository = $this->createMock(BuildingEntranceReadRepositoryInterface::class);

        $this->bridge = new AddressSearchToBuildingDataBridge(
            $buildingEntranceRepository,
            $this->addressSearchRepository,
            $this->messageBus,
            new NullLogger(),
        );
    }

    public function testEventHandled(): void
    {
        $deleteBeforeTime = new \DateTimeImmutable('2022-11-22 10:11:12');
        $this->addressSearchRepository->expects($this->once())
            ->method('deleteByImportedAtBefore')
            ->with($deleteBeforeTime, null)
        ;
        $this->messageBus->expects($this->never())->method('dispatch');

        $event = new BuildingEntrancesHaveBeenPruned(
            new \DateTimeImmutable('2022-11-22 10:11:12'),
            null,
        );
        $this->bridge->onBuildingEntrancesDeleted($event);
    }

    public function testEventHandledWithCountry(): void
    {
        $deleteBeforeTime = new \DateTimeImmutable('2022-11-22 10:11:12');
        $this->addressSearchRepository->expects($this->once())
            ->method('deleteByImportedAtBefore')
            ->with($deleteBeforeTime, CountryCodeEnum::CH)
        ;
        $this->messageBus->expects($this->never())->method('dispatch');

        $event = new BuildingEntrancesHaveBeenPruned(
            new \DateTimeImmutable('2022-11-22 10:11:12'),
            CountryCodeEnum::CH,
        );
        $this->bridge->onBuildingEntrancesDeleted($event);
    }
}
