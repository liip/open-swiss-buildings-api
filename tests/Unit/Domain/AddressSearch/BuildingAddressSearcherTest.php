<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\AddressSearch;

use App\Domain\AddressSearch\BuildingAddressSearcher;
use App\Domain\AddressSearch\Contract\AddressSearchReadRepositoryInterface;
use App\Domain\AddressSearch\Model\AddressSearch;
use App\Domain\AddressSearch\Model\BuildingAddressScored;
use App\Domain\AddressSearch\Model\PlaceScored;
use App\Infrastructure\PostGis\Coordinates;
use App\Tests\Util\BuildingAddressModelBuilder;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[Small]
final class BuildingAddressSearcherTest extends TestCase
{
    private MockObject&AddressSearchReadRepositoryInterface $addressSearchRepositoryReader;

    private BuildingAddressSearcher $addressSearcher;

    protected function setUp(): void
    {
        $this->addressSearchRepositoryReader = $this->createMock(AddressSearchReadRepositoryInterface::class);
        $this->addressSearcher = new BuildingAddressSearcher($this->addressSearchRepositoryReader);
    }

    public function testSearchPlaces(): void
    {
        $buildingAddress = BuildingAddressModelBuilder::buildBuildingAddress(BuildingAddressModelBuilder::UUID1);

        $this->addressSearchRepositoryReader->expects($this->once())
            ->method('searchAddress')
            ->willReturnCallback(function (AddressSearch $addressSearch, bool $debug) use ($buildingAddress): array {
                $this->assertSame(200, $addressSearch->limit);
                $this->assertSame('query string', $addressSearch->filterByQuery);
                $this->assertNull($addressSearch->minScore);
                $this->assertFalse($debug);

                return [new BuildingAddressScored(20, '', $buildingAddress)];
            })
        ;

        /** @var list<PlaceScored> $results */
        $results = iterator_to_array($this->addressSearcher->searchPlaces(new AddressSearch(
            limit: 200,
            filterByQuery: 'query string',
        )));

        $this->assertCount(1, $results);

        $this->assertSame(20, $results[0]->score);
        $this->assertSame(BuildingAddressModelBuilder::UUID1, $results[0]->place->identifier);
        $this->assertNotInstanceOf(Coordinates::class, $results[0]->place->geo);

        $this->assertSame(BuildingAddressModelBuilder::LOCALITY, $results[0]->place->postalAddress->addressLocality);
        $this->assertSame(BuildingAddressModelBuilder::POSTAL_CODE, $results[0]->place->postalAddress->postalCode);
    }
}
