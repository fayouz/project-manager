<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Integration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\JenkinsConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class JenkinsConnectorTest extends TestCase
{
    public function testSupports(): void
    {
        $connector = new JenkinsConnector(new MockHttpClient());

        $this->assertTrue($connector->supports('jenkins'));
        $this->assertTrue($connector->supports('JENKINS'));
        $this->assertFalse($connector->supports('gitea'));
    }

    public function testTestConnectionSuccess(): void
    {
        $mockResponse = new MockResponse(
            json_encode(['nodeDescription' => 'Primary Jenkins Master', '_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR),
            [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'application/json',
                    'x-jenkins' => '2.440.1',
                ],
            ]
        );

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new JenkinsConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Jenkins Server');
        $server->setHost('jenkins.example.com');
        $server->setPort(443);
        $server->setUsername('admin');
        $server->setPassword('secret_token');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'https']);

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à Jenkins', $result->message);
        $this->assertStringContainsString('2.440.1', $result->message);
        $this->assertSame('2.440.1', $result->details['version'] ?? null);
    }

    public function testTestConnectionUnauthorized(): void
    {
        $mockResponse = new MockResponse('Unauthorized', [
            'http_code' => 401,
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new JenkinsConnector($httpClient);

        $server = new Server();
        $server->setName('Jenkins Server');
        $server->setHost('jenkins.example.com');
        $server->setPort(443);
        $server->setUsername('admin');
        $server->setPassword('invalid_token');

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Échec d'authentification Jenkins (HTTP 401)", $result->message);
    }

    public function testTestConnectionMissingServer(): void
    {
        $connector = new JenkinsConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('jenkins');

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Aucun serveur n'est associé", $result->message);
    }
}
