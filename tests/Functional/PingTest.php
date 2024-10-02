<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\Medium;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[Medium]
final class PingTest extends WebTestCase
{
    public function testPingWorks(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/ping');

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('', $client->getResponse()->getContent());
    }
}
