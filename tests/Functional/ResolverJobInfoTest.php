<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\Large;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

#[Large]
final class ResolverJobInfoTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testResolverJobInfoReturns404ForNotExistingId(): void
    {
        $this->client->request(Request::METHOD_GET, '/resolve/jobs/' . Uuid::v7());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testResolverJobInfoReturns404ForInvalidId(): void
    {
        $this->client->request(Request::METHOD_GET, '/resolve/jobs/asdfghjkl');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testResolverJobResultReturns404ForNotExistingId(): void
    {
        $this->client->request(Request::METHOD_GET, '/resolve/jobs/' . Uuid::v7() . '/results');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testResolverJobResultReturns404ForInvalidId(): void
    {
        $this->client->request(Request::METHOD_GET, '/resolve/jobs/asdfghjkl/results');

        $this->assertResponseStatusCodeSame(404);
    }
}
