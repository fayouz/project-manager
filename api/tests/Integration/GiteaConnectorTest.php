<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Integration;
use App\Entity\Proxy;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\GiteaConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GiteaConnectorTest extends TestCase
{
    public function testSupports(): void
    {
        $connector = new GiteaConnector(new MockHttpClient());

        $this->assertTrue($connector->supports('gitea'));
        $this->assertTrue($connector->supports('GITEA'));
        $this->assertFalse($connector->supports('jenkins'));
    }

    public function testTestConnectionSuccessWithToken(): void
    {
        $versionResponse = new MockResponse(
            json_encode(['version' => '1.21.3'], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );
        $userResponse = new MockResponse(
            json_encode(['username' => 'gitadmin', 'login' => 'gitadmin', 'id' => 1], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient([$versionResponse, $userResponse]);
        $connector = new GiteaConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Gitea Server');
        $server->setHost('git.example.com');
        $server->setPassword('valid_access_token');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'https']);

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à Gitea', $result->message);
        $this->assertStringContainsString('1.21.3', $result->message);
        $this->assertStringContainsString('gitadmin', $result->message);
        $this->assertSame('1.21.3', $result->details['version'] ?? null);
        $this->assertSame('gitadmin', $result->details['username'] ?? null);
    }

    public function testTestConnectionInvalidToken(): void
    {
        $versionResponse = new MockResponse(
            json_encode(['version' => '1.21.3'], JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );
        $userResponse = new MockResponse('Unauthorized', ['http_code' => 401]);

        $httpClient = new MockHttpClient([$versionResponse, $userResponse]);
        $connector = new GiteaConnector($httpClient);

        $server = new Server();
        $server->setName('Gitea Server');
        $server->setHost('git.example.com');
        $server->setPassword('bad_token');

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Échec d'authentification Gitea (HTTP 401)", $result->message);
    }

    public function testTestConnectionNotFound(): void
    {
        $mockResponse = new MockResponse('Not Found', ['http_code' => 404]);
        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new GiteaConnector($httpClient);

        $server = new Server();
        $server->setName('Missing Gitea');
        $server->setHost('not-found.example.com');

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString('Instance Gitea introuvable', $result->message);
    }

    public function testTestConnectionMissingServer(): void
    {
        $connector = new GiteaConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('gitea');

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Aucun serveur n'est associé", $result->message);
    }

    public function testProxyUsedFromIntegrationOverride(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;

            return new MockResponse(json_encode(['version' => '1.21.3'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
            ]);
        });

        $serverProxy = new Proxy();
        $serverProxy->setUrl('http://server-proxy:8080');
        $serverProxy->setEnabled(true);

        $integrationProxy = new Proxy();
        $integrationProxy->setUrl('http://integration-proxy:8080');
        $integrationProxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-gitea.com');
        $server->setProxy($serverProxy);

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);
        $integration->setProxy($integrationProxy);

        $connector = new GiteaConnector($httpClient);
        $connector->testConnection($integration);

        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('http://integration-proxy:8080', $capturedOptions['proxy']);
    }
}
