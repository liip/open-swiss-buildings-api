<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Domain\BuildingData\Entity\BuildingEntrance;
use App\Domain\Resolving\Entity\ResolverJob as ResolverJobEntity;
use App\Domain\Resolving\Model\CsvRow;
use App\Domain\Resolving\Model\Job\ResolverJob;
use App\Domain\Resolving\Model\ResolverTypeEnum;
use App\Infrastructure\Csv\PhpCsvReader;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\BrowserKitAssertionsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final readonly class ResolvingApi
{
    use BrowserKitAssertionsTrait;

    public function __construct(
        private KernelBrowser $client,
        private ContainerInterface $container,
    ) {
        $this->cleanUp();
    }

    public function tearDown(): void
    {
        $this->cleanUp();
    }

    public function createJob(ResolverTypeEnum $type, string $content): ResolverJob
    {
        $path = match ($type) {
            ResolverTypeEnum::BUILDING_IDS => 'building-ids',
            ResolverTypeEnum::MUNICIPALITIES_CODES => 'municipalities-codes',
            ResolverTypeEnum::GEO_JSON => 'geo-json',
            ResolverTypeEnum::ADDRESS_SEARCH => 'address-search',
        };

        $this->client->request(Request::METHOD_POST, "/resolve/{$path}", [], [], ['CONTENT_TYPE' => 'text/csv'], $content);
        $response = $this->client->getResponse();

        $json = $this->getJsonOfResponse($response);

        try {
            return ResolverJob::fromArray($json);
        } catch (\UnexpectedValueException $e) {
            Assert::fail("Unable to decode response data for resolver job: {$e->getMessage()}");
        }
    }

    public function getJobInfo(string $id): ResolverJob
    {
        $this->client->request(Request::METHOD_GET, "/resolve/jobs/{$id}");
        $response = $this->client->getResponse();

        $json = $this->getJsonOfResponse($response);

        try {
            return ResolverJob::fromArray($json);
        } catch (\UnexpectedValueException $e) {
            Assert::fail("Unable to decode response data for resolver job: {$e->getMessage()}");
        }
    }

    /**
     * @return list<CsvRow>
     */
    public function getJobResults(string $id): array
    {
        $this->client->request(Request::METHOD_GET, "/resolve/jobs/{$id}/results", [], [], ['HTTP_ACCEPT' => 'text/csv']);

        $response = $this->client->getResponse();
        Assert::assertTrue($response->isSuccessful());
        Assert::assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $this->client->getInternalResponse()->getContent();

        $resource = fopen('php://memory', 'r+');
        Assert::assertIsResource($resource);
        fwrite($resource, $content);
        rewind($resource);

        $csv =  new PhpCsvReader($resource);

        $rows = [];
        foreach ($csv->read() as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function getJobResultsAsJson(string $id): array
    {
        $this->client->request(Request::METHOD_GET, "/resolve/jobs/{$id}/results", [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->client->getResponse();
        Assert::assertTrue($response->isSuccessful());
        Assert::assertSame('application/json', $response->headers->get('Content-Type'));

        $content = $this->client->getInternalResponse()->getContent();

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $expected
     */
    public function assertCsvRow(array $expected, CsvRow $actual): void
    {
        foreach ($expected as $column => $value) {
            try {
                Assert::assertSame($value, $actual->get($column), "Column {$column} in row #{$actual->number} should match");
            } catch (\InvalidArgumentException $e) {
                Assert::fail("CSV row was expected to have a column \"{$column}\", but there is no such column!");
            }
        }
    }

    /**
     * @return array<string|int, mixed>
     */
    private function getJsonOfResponse(Response $response): array
    {
        Assert::assertTrue($response->isSuccessful(), (string) $response->getContent());
        Assert::assertSame('application/json', $response->headers->get('Content-Type'));

        return $this->decodeToArray($response->getContent());
    }

    /**
     * @return array<string|int, mixed>
     */
    private function decodeToArray(mixed $content): array
    {
        Assert::assertIsString($content);
        Assert::assertJson($content);
        $json = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        Assert::assertIsArray($json);

        return $json;
    }

    private function cleanUp(): void
    {
        $jobRepository = $this->container->get('doctrine')->getRepository(ResolverJobEntity::class);
        foreach ($jobRepository->getJobs() as $job) {
            $jobRepository->delete(Uuid::fromString($job->id));
        }

        $buildingRepository = $this->container->get('doctrine')->getRepository(BuildingEntrance::class);
        $buildingRepository->deleteAll();
    }
}
