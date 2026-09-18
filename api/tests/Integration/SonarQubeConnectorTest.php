<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Integration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\SonarQubeConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SonarQubeConnectorTest extends TestCase
{
    public function testSupports(): void
    {
        $connector = new SonarQubeConnector(new MockHttpClient());

        $this->assertTrue($connector->supports('sonarqube'));
        $this->assertTrue($connector->supports('SONARQUBE'));
        $this->assertSame('sonarqube', $connector->getType());
        $this->assertSame('SonarQube', $connector->getName());
        $this->assertFalse($connector->supports('jenkins'));
        $this->assertFalse($connector->supports('gitea'));
        $this->assertFalse($connector->supports('mantis'));
    }

    public function testTestConnectionBasicAuthSuccess(): void
    {
        $statusResponse = new MockResponse(
            json_encode([
                'id' => '7B90DB84-AX_6li5BqkQxXgedqqPd',
                'version' => '25.1.0.102122',
                'status' => 'UP',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $userResponse = new MockResponse(
            json_encode([
                'isLoggedIn' => true,
                'login' => 'pinf14',
                'name' => 'BOULOUSSA Faez',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $projectsResponse = new MockResponse(
            json_encode([
                'paging' => [
                    'pageIndex' => 1,
                    'pageSize' => 1,
                    'total' => 89,
                ],
                'components' => [],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $httpClient = new MockHttpClient([$statusResponse, $userResponse, $projectsResponse]);
        $connector = new SonarQubeConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('SonarQube Server');
        $server->setHost('sonarqube.bm-energies.com');
        $server->setPort(9000);
        $server->setUsername('PINF14');
        $server->setPassword('secret_password');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'http']);

        $integration = new Integration();
        $integration->setType('sonarqube');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à SonarQube', $result->message);
        $this->assertStringContainsString('25.1.0.102122', $result->message);
        $this->assertStringContainsString('BOULOUSSA Faez', $result->message);
        $this->assertStringContainsString('89 projets accessibles', $result->message);
        $this->assertSame('25.1.0.102122', $result->details['version'] ?? null);
        $this->assertSame('BOULOUSSA Faez', $result->details['username'] ?? null);
        $this->assertSame(89, $result->details['projects_count'] ?? null);
        $this->assertSame('UP', $result->details['status'] ?? null);
    }

    public function testTestConnectionTokenSuccess(): void
    {
        $statusResponse = new MockResponse(
            json_encode([
                'id' => '12345',
                'version' => '10.5.0',
                'status' => 'UP',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $userResponse = new MockResponse(
            json_encode([
                'isLoggedIn' => true,
                'login' => 'token-user',
                'name' => 'CI Service Account',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $projectsResponse = new MockResponse(
            json_encode([
                'paging' => [
                    'pageIndex' => 1,
                    'pageSize' => 1,
                    'total' => 1,
                ],
                'components' => [],
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $httpClient = new MockHttpClient([$statusResponse, $userResponse, $projectsResponse]);
        $connector = new SonarQubeConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('SonarQube Cloud');
        $server->setHost('sonarcloud.io');
        $server->setPassword('squ_my_token');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'https']);

        $integration = new Integration();
        $integration->setType('sonarqube');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à SonarQube', $result->message);
        $this->assertStringContainsString('10.5.0', $result->message);
        $this->assertStringContainsString('CI Service Account', $result->message);
        $this->assertStringContainsString('1 projet accessible', $result->message);
    }

    public function testTestConnectionInvalidCredentials(): void
    {
        $statusResponse = new MockResponse(
            json_encode([
                'id' => '12345',
                'version' => '10.5.0',
                'status' => 'UP',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $userResponse = new MockResponse('Unauthorized', ['http_code' => 401]);

        $httpClient = new MockHttpClient([$statusResponse, $userResponse]);
        $connector = new SonarQubeConnector($httpClient);

        $server = new Server();
        $server->setName('SonarQube Server');
        $server->setHost('sonarqube.example.com');
        $server->setPort(9000);
        $server->setUsername('PINF14');
        $server->setPassword('wrong_password');

        $integration = new Integration();
        $integration->setType('sonarqube');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Échec d'authentification SonarQube (HTTP 401)", $result->message);
    }

    public function testTestConnectionAnonymousSuccess(): void
    {
        $statusResponse = new MockResponse(
            json_encode([
                'id' => '12345',
                'version' => '10.5.0',
                'status' => 'UP',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $httpClient = new MockHttpClient([$statusResponse]);
        $connector = new SonarQubeConnector($httpClient);

        $server = new Server();
        $server->setName('SonarQube Public');
        $server->setHost('sonarqube.example.com');
        $server->setPort(9000);

        $integration = new Integration();
        $integration->setType('sonarqube');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Instance SonarQube accessible (version 10.5.0) (aucun identifiant configuré)', $result->message);
    }

    public function testTestConnectionServerDown(): void
    {
        $statusResponse = new MockResponse(
            json_encode([
                'id' => '12345',
                'version' => '10.5.0',
                'status' => 'STARTING',
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
        );

        $httpClient = new MockHttpClient([$statusResponse]);
        $connector = new SonarQubeConnector($httpClient);

        $server = new Server();
        $server->setName('SonarQube Server');
        $server->setHost('sonarqube.example.com');
        $server->setPort(9000);

        $integration = new Integration();
        $integration->setType('sonarqube');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Le serveur SonarQube n'est pas opérationnel (état: STARTING)", $result->message);
    }

    public function testTestConnectionMissingServer(): void
    {
        $connector = new SonarQubeConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('sonarqube');

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Aucun serveur n'est associé", $result->message);
    }
}
