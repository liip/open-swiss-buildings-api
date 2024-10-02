<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Bridge;

use App\Domain\AddressSearch\Contract\AddressSearchWriteRepositoryInterface;
use App\Domain\Bridge\AddressSearchToBuildingDataBridge;
use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Event\BuildingEntrancesHaveBeenPruned;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;

#[Small]
final class BuildingAddressToBuildingDataBridgeTest extends TestCase
{
    private BuildingEntranceReadRepositoryInterface&MockObject $buildingEntranceRepository;
    private AddressSearchWriteRepositoryInterface&MockObject $addressSearchRepository;
    private AddressSearchToBuildingDataBridge $bridge;

    protected function setUp(): void
    {
        $this->buildingEntranceRepository = $this->createMock(BuildingEntranceReadRepositoryInterface::class);
        $this->addressSearchRepository = $this->createMock(AddressSearchWriteRepositoryInterface::class);
        $this->bridge = new AddressSearchToBuildingDataBridge(
            $this->buildingEntranceRepository,
            $this->addressSearchRepository,
            $this->createStub(MessageBusInterface::class),
            new NullLogger(),
        );
    }

    public function testEventHandled(): void
    {
        $deleteBeforeTime = new \DateTimeImmutable('2022-11-22 10:11:12');
        $this->addressSearchRepository->expects($this->once())
            ->method('deleteByImportedAtBefore')
            ->with($deleteBeforeTime)
        ;

        $event = new BuildingEntrancesHaveBeenPruned(new \DateTimeImmutable('2022-11-22 10:11:12'));
        $this->bridge->onBuildingEntrancesDeleted($event);
    }
}
