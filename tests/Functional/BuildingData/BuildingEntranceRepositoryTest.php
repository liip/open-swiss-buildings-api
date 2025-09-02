<?php

declare(strict_types=1);

namespace App\Tests\Functional\BuildingData;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\BuildingData\Repository\BuildingEntranceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BuildingEntranceRepositoryTest extends KernelTestCase
{
    private BuildingEntranceRepository $repository;

    protected function setUp(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $em->getRepository(BuildingEntrance::class);
    }

    public function testPrune(): void
    {
        $this->assertSame(0, $this->repository->deleteOutdatedBuildingEntrances(90));
    }
}
