<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Integration;
use App\Entity\Proxy;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\ORM\EntityManagerInterface;

class ProxyApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $lockFile = self::getContainer()->getParameter('kernel.project_dir').'/config/install.lock';
        if (!file_exists($lockFile)) {
            file_put_contents($lockFile, '');
        }
    }

    public function testGetCollection(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/api/proxies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateProxyValidationFailsOnBlankOrInvalid(): void
    {
        $client = static::createClient();

        // Blank name and url
        $client->request('POST', '/api/proxies', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => '',
                'url' => 'not-a-valid-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCrudOperationsAndHiddenPassword(): void
    {
        $client = static::createClient();

        // 1. Create Proxy with password
        $response = $client->request('POST', '/api/proxies', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Corporate Squid Proxy',
                'url' => 'http://proxy.internal.corp:3128',
                'username' => 'proxy_admin',
                'password' => 'super_secret_proxy_pass',
                'noProxy' => 'localhost,127.0.0.1,internal.corp',
                'enabled' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertArrayHasKey('@id', $data);
        $iri = $data['@id'];
        $this->assertSame('Corporate Squid Proxy', $data['name']);
        $this->assertSame('http://proxy.internal.corp:3128', $data['url']);
        $this->assertSame('proxy_admin', $data['username']);
        $this->assertSame('localhost,127.0.0.1,internal.corp', $data['noProxy']);
        $this->assertTrue($data['enabled']);
        // Verify password is NOT exposed in response
        $this->assertArrayNotHasKey('password', $data);

        // 2. GET item
        $getResponse = $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $getData = $getResponse->toArray();
        $this->assertSame('Corporate Squid Proxy', $getData['name']);
        $this->assertArrayNotHasKey('password', $getData);

        // 3. PATCH update
        $patchResponse = $client->request('PATCH', $iri, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Updated Corporate Proxy',
                'enabled' => false,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $patchData = $patchResponse->toArray();
        $this->assertSame('Updated Corporate Proxy', $patchData['name']);
        $this->assertFalse($patchData['enabled']);

        // 4. Test SearchFilter on name and BooleanFilter on enabled
        $filterResponse = $client->request('GET', '/api/proxies?name=Updated&enabled=false', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $filterData = $filterResponse->toArray();
        $totalItems = $filterData['totalItems'] ?? $filterData['hydra:totalItems'] ?? count($filterData['member'] ?? []);
        $this->assertGreaterThanOrEqual(1, $totalItems);

        // 5. DELETE
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);

        // Verify it no longer exists
        $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteProxySetsNullOnAssociatedServerAndIntegration(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // Create Proxy directly
        $proxy = new Proxy();
        $proxy->setName('Proxy To Delete');
        $proxy->setUrl('http://temporary-proxy:8080');
        $proxy->setEnabled(true);
        $em->persist($proxy);

        // Ensure ServerType and ServerAuthenticationType exist
        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if (null === $serverType->getId()) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }
        $authType = $em->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Basic']) ?? new ServerAuthenticationType();
        if (null === $authType->getId()) {
            $authType->setName('Basic');
            $em->persist($authType);
        }

        $server = new Server();
        $server->setName('Server With Proxy');
        $server->setHost('server.test.local');
        $server->setPort(80);
        $server->setUsername('srv_user');
        $server->setPassword('srv_pass');
        $server->setType($serverType);
        $server->setAuthenticationType($authType);
        $server->setProxy($proxy);
        $em->persist($server);

        $integration = new Integration();
        $integration->setName('Integration With Proxy');
        $integration->setType('jenkins');
        $integration->setServer($server);
        $integration->setProxy($proxy);
        $em->persist($integration);

        $em->flush();

        $proxyId = $proxy->getId();
        $serverId = $server->getId();
        $integrationId = $integration->getId();

        $this->assertNotNull($proxyId);

        // DELETE proxy via API
        $client->request('DELETE', '/api/proxies/'.$proxyId);
        $this->assertResponseStatusCodeSame(204);

        // Clear entityManager cache and re-fetch Server and Integration
        $em->clear();

        $reloadedServer = $em->getRepository(Server::class)->find($serverId);
        $reloadedIntegration = $em->getRepository(Integration::class)->find($integrationId);

        $this->assertNotNull($reloadedServer);
        $this->assertNull($reloadedServer->getProxy());

        $this->assertNotNull($reloadedIntegration);
        $this->assertNull($reloadedIntegration->getProxy());
    }
}
