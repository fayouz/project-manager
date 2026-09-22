<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Integration;
use App\Entity\NexusIntegrationParam;
use App\Entity\Proxy;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\NexusConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NexusConnectorTest extends TestCase
{
    public function testSupports(): void
    {
        $connector = new NexusConnector(new MockHttpClient());

        $this->assertTrue($connector->supports('nexus'));
        $this->assertTrue($connector->supports('NEXUS'));
        $this->assertFalse($connector->supports('jenkins'));
    }

    public function testTestConnectionSuccess(): void
    {
        $statusResponse = new MockResponse('OK', [
            'http_code' => 200,
            'response_headers' => [
                'server' => 'Nexus/3.94.0-12 (COMMUNITY)',
            ],
        ]);

        $reposResponse = new MockResponse(
            json_encode([
                ['name' => 'maven-releases', 'format' => 'maven2', 'type' => 'hosted'],
                ['name' => 'maven-snapshots', 'format' => 'maven2', 'type' => 'hosted'],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient([$statusResponse, $reposResponse]);
        $connector = new NexusConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Nexus Server');
        $server->setHost('nexus.bm-energies.com');
        $server->setPort(8081);
        $server->setUsername('PINF14');
        $server->setPassword('secret');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'http', 'proxy' => 'direct']);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion à Nexus Repository (v3.94.0-12) réussie', $result->message);
        $this->assertSame('3.94.0-12', $result->details['version'] ?? null);
        $this->assertSame(2, $result->details['repositories_count'] ?? null);
    }

    public function testTestConnectionUnauthorized(): void
    {
        $statusResponse = new MockResponse('OK', ['http_code' => 200]);
        $reposResponse = new MockResponse('Unauthorized', ['http_code' => 401]);

        $httpClient = new MockHttpClient([$statusResponse, $reposResponse]);
        $connector = new NexusConnector($httpClient);

        $server = new Server();
        $server->setHost('nexus.bm-energies.com');
        $server->setPort(8081);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString('Identifiants invalides pour accéder à Nexus Repository', $result->message);
    }

    public function testTestConnectionMissingServer(): void
    {
        $connector = new NexusConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('nexus');

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Aucun serveur n'est associé", $result->message);
    }

    public function testGetProjects(): void
    {
        $reposResponse = new MockResponse(
            json_encode([
                ['name' => 'maven-releases', 'format' => 'maven2', 'type' => 'hosted', 'url' => 'http://nexus.bm-energies.com:8081/repository/maven-releases/'],
                ['name' => 'npm-internal', 'format' => 'npm', 'type' => 'hosted', 'url' => 'http://nexus.bm-energies.com:8081/repository/npm-internal/'],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient([$reposResponse]);
        $connector = new NexusConnector($httpClient);

        $server = new Server();
        $server->setHost('nexus.bm-energies.com');
        $server->setPort(8081);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $projects = $connector->getProjects($integration);

        $this->assertCount(2, $projects);
        $this->assertSame('maven-releases', $projects[0]['id']);
        $this->assertSame('maven-releases (maven2 - hosted)', $projects[0]['name']);
        $this->assertSame('npm-internal', $projects[1]['id']);
    }

    public function testGetLiveData(): void
    {
        $reposResponse = new MockResponse(
            json_encode([
                ['name' => 'maven-releases', 'format' => 'maven2', 'type' => 'hosted'],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $componentsResponse = new MockResponse(
            json_encode([
                'items' => [
                    [
                        'id' => 'comp-1',
                        'repository' => 'maven-releases',
                        'format' => 'maven2',
                        'group' => 'com.bmenergies',
                        'name' => 'scrapper-service',
                        'version' => '1.0.0',
                        'assets' => [
                            [
                                'id' => 'asset-1',
                                'downloadUrl' => 'http://nexus/asset-1.jar',
                                'path' => '/com/bmenergies/scrapper-service/1.0.0/scrapper-service-1.0.0.jar',
                                'format' => 'maven2',
                                'fileSize' => 1024,
                                'contentType' => 'application/java-archive',
                                'lastModified' => '2026-03-20T10:00:00Z',
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $assetsResponse = new MockResponse(
            json_encode([
                'items' => [
                    [
                        'id' => 'asset-1',
                        'downloadUrl' => 'http://nexus/asset-1.jar',
                        'path' => '/com/bmenergies/scrapper-service/1.0.0/scrapper-service-1.0.0.jar',
                        'format' => 'maven2',
                        'fileSize' => 1024,
                        'contentType' => 'application/java-archive',
                        'lastModified' => '2026-03-20T10:00:00Z',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient([$reposResponse, $componentsResponse, $assetsResponse]);
        $connector = new NexusConnector($httpClient);

        $server = new Server();
        $server->setHost('nexus.bm-energies.com');
        $server->setPort(8081);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $param = new NexusIntegrationParam();
        $param->setRepository('maven-releases');
        $param->setGroup('com.bmenergies');

        $data = $connector->getLiveData($integration, $param);

        $this->assertSame('maven-releases', $data['repository']);
        $this->assertSame('com.bmenergies', $data['group']);
        $this->assertSame('maven2', $data['format']);
        $this->assertSame(1, $data['componentsCount']);
        $this->assertCount(1, $data['components']);
        $this->assertSame('scrapper-service', $data['components'][0]['name']);
        $this->assertSame(1, $data['assetsCount']);
    }

    public function testProxyUsedFromIntegrationOverride(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;

            return new MockResponse(json_encode(['status' => 'OK'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['server' => 'Nexus/3.60.0-02'],
            ]);
        });

        $serverProxy = new Proxy();
        $serverProxy->setUrl('http://server-proxy:8080');
        $serverProxy->setEnabled(true);

        $integrationProxy = new Proxy();
        $integrationProxy->setUrl('http://integration-proxy:8080');
        $integrationProxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-nexus.com');
        $server->setProxy($serverProxy);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);
        $integration->setProxy($integrationProxy);

        $connector = new NexusConnector($httpClient);
        $connector->testConnection($integration);

        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('http://integration-proxy:8080', $capturedOptions['proxy']);
    }

    public function testTestConnectionWithAucuneAuthType(): void
    {
        $capturedRequests = [];
        $statusResponse = new MockResponse('OK', [
            'http_code' => 200,
            'response_headers' => ['server' => 'Nexus/3.94.0-12 (COMMUNITY)'],
        ]);

        $reposResponse = new MockResponse(
            json_encode([
                ['name' => 'public-repo-1', 'format' => 'maven2', 'type' => 'hosted'],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedRequests, $statusResponse, $reposResponse): MockResponse {
            $capturedRequests[] = ['method' => $method, 'url' => $url, 'options' => $options];
            if (str_contains($url, '/status')) {
                return $statusResponse;
            }

            return $reposResponse;
        });

        $connector = new NexusConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Aucune');

        $server = new Server();
        $server->setName('Nexus Server');
        $server->setHost('nexus.bm-energies.com');
        $server->setPort(8081);
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertSame(1, $result->details['repositories_count'] ?? null);

        // Verify that no auth_basic or Authorization header was sent
        foreach ($capturedRequests as $req) {
            $this->assertArrayNotHasKey('auth_basic', $req['options']);
            $this->assertArrayNotHasKey('Authorization', $req['options']['headers'] ?? []);
        }
    }

    public function testFetchRepositoriesCumulatesPrivateAndPublicRepos(): void
    {
        $callCount = 0;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$callCount): MockResponse {
            ++$callCount;
            if (isset($options['auth_basic'])) {
                // Requête authentifiée : retourne les dépôts privés
                return new MockResponse(json_encode([
                    ['name' => 'private-repo-1', 'format' => 'maven2', 'type' => 'hosted'],
                ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }

            // Requête anonyme : retourne les dépôts publics
            return new MockResponse(json_encode([
                ['name' => 'public-repo-1', 'format' => 'npm', 'type' => 'hosted'],
                ['name' => 'private-repo-1', 'format' => 'maven2', 'type' => 'hosted'], // doublon dédupliqué
            ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        });

        $connector = new NexusConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setHost('nexus.bm-energies.com');
        $server->setUsername('PINF14');
        $server->setPassword('secret');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('nexus');
        $integration->setServer($server);

        $repos = $connector->fetchRepositories($integration);

        $this->assertCount(2, $repos);
        $names = array_column($repos, 'name');
        $this->assertContains('private-repo-1', $names);
        $this->assertContains('public-repo-1', $names);
    }
}
