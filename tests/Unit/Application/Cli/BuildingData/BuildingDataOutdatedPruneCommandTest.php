<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Cli\BuildingData;

use App\Application\Cli\BuildingData\BuildingDataOutdatedPruneCommand;
use App\Domain\BuildingData\Contract\BuildingEntranceReadRepositoryInterface;
use App\Domain\BuildingData\Contract\BuildingEntranceWriteRepositoryInterface;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[Small]
final class BuildingDataOutdatedPruneCommandTest extends TestCase
{
    private MockObject&BuildingEntranceReadRepositoryInterface $buildingEntranceReadRepository;
    private MockObject&BuildingEntranceWriteRepositoryInterface $buildingEntranceWriteRepository;
    private BuildingDataOutdatedPruneCommand $command;

    protected function setUp(): void
    {
        $this->buildingEntranceReadRepository = $this->createMock(BuildingEntranceReadRepositoryInterface::class);
        $this->buildingEntranceWriteRepository = $this->createMock(BuildingEntranceWriteRepositoryInterface::class);

        $this->command = new BuildingDataOutdatedPruneCommand(
            $this->buildingEntranceReadRepository,
            $this->buildingEntranceWriteRepository,
        );
    }

    public function testExecuteDeletesEntries(): void
    {
        $this->buildingEntranceReadRepository->expects($this->once())
            ->method('countOutdatedBuildingEntrances')
            ->willReturn(100)
        ;
        $this->buildingEntranceWriteRepository->expects($this->once())
            ->method('deleteOutdatedBuildingEntrances')
            ->willReturn(100)
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
