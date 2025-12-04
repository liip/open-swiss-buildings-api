<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataLI;

use App\Domain\Registry\Contract\RegistryBuildingDataRepositoryInterface;
use App\Domain\Registry\DataLI\Model\BuildingDataCsvRecord;
use App\Domain\Registry\Model\BuildingEntranceFilter;
use App\Infrastructure\Model\CountryCodeEnum;
use App\Infrastructure\Pagination;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\UnavailableStream;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RegistryBuildingDataRepository implements RegistryBuildingDataRepositoryInterface
{
    private const int DEDUPLICATE_BUFFER_LENGTH = 5;

    /**
     * @var Reader<BuildingDataCsvRecord[]>|null
     */
    private ?Reader $reader = null;

    public function __construct(
        #[Autowire(env: 'resolve:REGISTRY_DATABASE_LI_FILE')]
        private readonly string $registryDatabaseFile,
    ) {}

    public static function country(): CountryCodeEnum
    {
        return CountryCodeEnum::LI;
    }

    public function countBuildingData(): int
    {
        return $this->getCsvReader()->count();
    }

    public function getBuildingData(): iterable
    {
        $pastSeen = [];
        $reader = $this->getCsvReader();
        /** @var BuildingDataCsvRecord $row */
        foreach ($reader->getRecordsAsObject(BuildingDataCsvRecord::class) as $i => $row) {
            // As the data contains some duplicated items, we keep a lookback list of the past item's hashes
            // and if the item is duplicated within it's buffer-window we skip it.
            $hash = $row->computeHash();
            if (\in_array($row->computeHash(), $pastSeen, true)) {
                continue;
            }

            // We keep the last $length items in a sort of "circular buffer" of DEDUPLICATE_BUFFER_LENGTH elements
            $pastSeen[$i % self::DEDUPLICATE_BUFFER_LENGTH] = $hash;

            yield $row->asBuildingEntranceData();
        }
    }

    public function getPaginatedBuildingData(Pagination $pagination, BuildingEntranceFilter $filter): iterable
    {
        $reader = $this->getCsvReader();
        $statement = (new Statement())
            ->offset($pagination->offset)
            ->limit($pagination->limit)
        ;

        if (null !== $filter->streetNames && [] !== $filter->streetNames) {
            $statement = $statement->andWhere('STRNAME', static fn(string $str): bool => \in_array($str, $filter->streetNames, true));
        }
        if (null !== $filter->municipalityNames && [] !== $filter->municipalityNames) {
            $statement = $statement->andWhere('GDENAME', static fn(string $str): bool => \in_array($str, $filter->municipalityNames, true));
        }
        if (null !== $filter->buildingIds && [] !== $filter->buildingIds) {
            $statement = $statement->andWhere('GEID', static fn(string $str): bool => \in_array($str, $filter->buildingIds, true));
        }
        if (null !== $filter->entranceIds && [] !== $filter->entranceIds) {
            $statement = $statement->andWhere('GEDID', static fn(string $str): bool => \in_array($str, $filter->entranceIds, true));
        }

        $items = $statement->process($reader);
        /** @var BuildingDataCsvRecord $row */
        foreach ($items->getRecordsAsObject(BuildingDataCsvRecord::class) as $row) {
            yield $row->asBuildingEntranceData();
        }
    }

    /**
     * @return Reader<BuildingDataCsvRecord[]>
     *
     * @throws Exception
     * @throws InvalidArgument
     * @throws UnavailableStream
     */
    private function getCsvReader(): Reader
    {
        if (null === $this->reader) {
            $this->reader = Reader::createFromPath($this->registryDatabaseFile);
            $this->reader->setHeaderOffset(0);
            $this->reader->setDelimiter(';');
            $this->reader->setEscape('');
        }

        return $this->reader;
    }
}
