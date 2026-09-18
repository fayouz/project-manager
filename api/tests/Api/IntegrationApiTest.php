<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\ORM\EntityManagerInterface;

class IntegrationApiTest extends ApiTestCase
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
        $response = $client->request('GET', '/api/integrations', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateIntegrationWithoutServerFails(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Invalid Integration Without Server',
                'type' => 'jenkins',
                'enabled' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateIntegrationWithServerAndTestEndpoints(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // Ensure ServerType and ServerAuthenticationType exist
        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if ($serverType->getId() === null) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }

        $authType = $em->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Basic']);
        if ($authType === null) {
            $authType = new ServerAuthenticationType();
            $authType->setName('Basic');
            $em->persist($authType);
        }

        $server = new Server();
        $server->setName('Test API Server');
        $server->setHost('127.0.0.1');
        $server->setPort(59999);
        $server->setUsername('api_test_user');
        $server->setPassword('api_test_pass');
        $server->setType($serverType);
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'http']);

        $em->persist($server);
        $em->flush();

        $serverIri = '/api/servers/'.$server->getId();

        $response = $client->request('POST', '/api/integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Automated Test Jenkins',
                'type' => 'jenkins',
                'enabled' => true,
                'server' => $serverIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertSame('Automated Test Jenkins', $data['name']);
        $this->assertSame('jenkins', $data['type']);
        $this->assertSame('unknown', $data['status']);
        $serverIriFound = is_array($data['server']) ? ($data['server']['@id'] ?? null) : $data['server'];
        $this->assertSame($serverIri, $serverIriFound);

        $iri = $data['@id'];

        // GET single item
        $getResponse = $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $getData = $getResponse->toArray();
        $getServerIriFound = is_array($getData['server']) ? ($getData['server']['@id'] ?? null) : $getData['server'];
        $this->assertSame($serverIri, $getServerIriFound);

        // Test transient connection endpoint with server
        $transientResponse = $client->request('POST', '/api/integrations/test', [
            'json' => [
                'type' => 'jenkins',
                'server' => $serverIri,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $transientData = $transientResponse->toArray();
        $this->assertFalse($transientData['success']);
        $this->assertSame('error', $transientData['status']);

        // Test existing integration test connection endpoint
        $testResponse = $client->request('POST', $iri . '/test');
        $this->assertResponseIsSuccessful();
        $testData = $testResponse->toArray();
        $this->assertFalse($testData['success']);
        $this->assertSame('error', $testData['status']);

        // Cleanup
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);

        $server = $em->find(Server::class, $server->getId());
        if ($server !== null) {
            $em->remove($server);
            $em->flush();
        }
    }

    public function testCreateMantisIntegrationAndTestEndpoint(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if ($serverType->getId() === null) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }

        $authType = $em->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Basic']);
        if ($authType === null) {
            $authType = new ServerAuthenticationType();
            $authType->setName('Basic');
            $em->persist($authType);
        }

        $server = new Server();
        $server->setName('Mantis Test Server');
        $server->setHost('127.0.0.1');
        $server->setPort(59998);
        $server->setUsername('mantis_user');
        $server->setPassword('mantis_pass');
        $server->setType($serverType);
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'http']);

        $em->persist($server);
        $em->flush();

        $serverIri = '/api/servers/'.$server->getId();

        $response = $client->request('POST', '/api/integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Mantis Bug Tracker Integration',
                'type' => 'mantis',
                'enabled' => true,
                'server' => $serverIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertSame('Mantis Bug Tracker Integration', $data['name']);
        $this->assertSame('mantis', $data['type']);
        $this->assertSame('unknown', $data['status']);
        $serverIriFound = is_array($data['server']) ? ($data['server']['@id'] ?? null) : $data['server'];
        $this->assertSame($serverIri, $serverIriFound);

        $iri = $data['@id'];

        // Test transient connection endpoint with mantis type
        $transientResponse = $client->request('POST', '/api/integrations/test', [
            'json' => [
                'type' => 'mantis',
                'server' => $serverIri,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $transientData = $transientResponse->toArray();
        $this->assertFalse($transientData['success']);
        $this->assertSame('error', $transientData['status']);

        // Cleanup
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);

        $server = $em->find(Server::class, $server->getId());
        if ($server !== null) {
            $em->remove($server);
            $em->flush();
        }
    }

    public function testCreateSonarQubeIntegrationAndTestEndpoint(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if ($serverType->getId() === null) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }

        $authType = $em->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Basic']);
        if ($authType === null) {
            $authType = new ServerAuthenticationType();
            $authType->setName('Basic');
            $em->persist($authType);
        }

        $server = new Server();
        $server->setName('SonarQube Test Server');
        $server->setHost('127.0.0.1');
        $server->setPort(59997);
        $server->setUsername('sonar_user');
        $server->setPassword('sonar_pass');
        $server->setType($serverType);
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'http']);

        $em->persist($server);
        $em->flush();

        $serverIri = '/api/servers/'.$server->getId();

        $response = $client->request('POST', '/api/integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'SonarQube Integration',
                'type' => 'sonarqube',
                'enabled' => true,
                'server' => $serverIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertSame('SonarQube Integration', $data['name']);
        $this->assertSame('sonarqube', $data['type']);
        $this->assertSame('unknown', $data['status']);
        $serverIriFound = is_array($data['server']) ? ($data['server']['@id'] ?? null) : $data['server'];
        $this->assertSame($serverIri, $serverIriFound);

        $iri = $data['@id'];

        // Test transient connection endpoint with sonarqube type
        $transientResponse = $client->request('POST', '/api/integrations/test', [
            'json' => [
                'type' => 'sonarqube',
                'server' => $serverIri,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $transientData = $transientResponse->toArray();
        $this->assertFalse($transientData['success']);
        $this->assertSame('error', $transientData['status']);

        // Cleanup
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);

        $server = $em->find(Server::class, $server->getId());
        if ($server !== null) {
            $em->remove($server);
            $em->flush();
        }
    }
}
