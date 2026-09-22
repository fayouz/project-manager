<?php

declare(strict_types=1);

namespace App\Tests\Integration\Connector;

use App\Entity\GiteaIntegrationParam;
use App\Entity\Integration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\GiteaConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GiteaConnectorTest extends TestCase
{
    public function testSuccessfulConnection(): void
    {
        $responses = [
            new MockResponse(json_encode(['version' => '1.26.2']), ['http_code' => 200]),
            new MockResponse(json_encode(['login' => 'PINF14']), ['http_code' => 200]),
        ];

        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses, &$capturedOptions) {
            $capturedOptions[] = $options;

            return array_shift($responses);
        });

        $connector = new GiteaConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Gitea');
        $server->setHost('gitea.bm-energies.com');
        $server->setPort(3000);
        $server->setUsername('PINF14');
        $server->setPassword('secret');
        $server->setAuthenticationType($authType);
        $server->setOptions([
            'protocol' => 'http',
            'proxy' => 'direct',
            'timeout' => 12.0,
        ]);

        $integration = new Integration();
        $integration->setName('Gitea Test');
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('1.26.2', $result->message);
        $this->assertStringContainsString('PINF14', $result->message);

        // Verify proxy was passed in request options
        $this->assertNotEmpty($capturedOptions);
        $this->assertSame('', $capturedOptions[0]['proxy']);
        $this->assertEquals(12.0, $capturedOptions[0]['timeout']);
    }

    public function testProxyUsedWhenExplicitlyConfiguredOnExternalHost(): void
    {
        $responses = [
            new MockResponse(json_encode(['version' => '1.26.2']), ['http_code' => 200]),
            new MockResponse(json_encode(['login' => 'PINF14']), ['http_code' => 200]),
        ];

        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses, &$capturedOptions) {
            $capturedOptions[] = $options;

            return array_shift($responses);
        });

        $connector = new GiteaConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Gitea');
        $server->setHost('gitea.external-cloud.com');
        $server->setPort(3000);
        $server->setUsername('PINF14');
        $server->setPassword('secret');
        $server->setAuthenticationType($authType);
        $server->setOptions([
            'protocol' => 'http',
            'proxy' => 'http://px.groupegdb.local:8080',
            'timeout' => 12.0,
        ]);

        $integration = new Integration();
        $integration->setName('Gitea Test');
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($capturedOptions);
        $this->assertSame('http://px.groupegdb.local:8080', $capturedOptions[0]['proxy']);
    }

    public function testProxyBypassedForInternalHost(): void
    {
        $responses = [
            new MockResponse(json_encode(['version' => '1.26.2']), ['http_code' => 200]),
            new MockResponse(json_encode(['login' => 'PINF14']), ['http_code' => 200]),
        ];

        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses, &$capturedOptions) {
            $capturedOptions[] = $options;

            return array_shift($responses);
        });

        $connector = new GiteaConnector($httpClient);

        $server = new Server();
        $server->setName('Gitea');
        $server->setHost('gitea.bm-energies.com');
        $server->setPort(3000);
        $server->setOptions([
            'protocol' => 'http',
            'proxy' => 'http://px.groupegdb.local:8080',
        ]);

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($capturedOptions);
        $this->assertSame('', $capturedOptions[0]['proxy']);
    }

    public function testCheckHealthBypassesProxyForInternalHost(): void
    {
        $responses = [
            new MockResponse(json_encode(['version' => '1.26.2']), ['http_code' => 200]),
            new MockResponse(json_encode(['login' => 'PINF14']), ['http_code' => 200]),
            new MockResponse(json_encode(['full_name' => 'RGZ/RegazScrapper', 'default_branch' => 'main']), ['http_code' => 200]),
        ];

        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses, &$capturedOptions) {
            $capturedOptions[] = $options;

            return array_shift($responses);
        });

        $connector = new GiteaConnector($httpClient);

        $server = new Server();
        $server->setName('Gitea');
        $server->setHost('gitea.bm-energies.com');
        $server->setPort(3000);
        $server->setOptions([
            'protocol' => 'http',
            'proxy' => 'http://px.groupegdb.local:8080',
        ]);

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $param = new GiteaIntegrationParam();
        $param->setRepository('RGZ/RegazScrapper');

        $result = $connector->checkHealth($integration, $param);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($capturedOptions);
        // The checkHealth repository call must also have proxy bypassed
        $repoCallOption = end($capturedOptions);
        $this->assertSame('', $repoCallOption['proxy']);
    }

    public function testConnectionFailureOnTimeoutOrHttpError(): void
    {
        $responses = [
            new MockResponse('Gateway Timeout', ['http_code' => 504]),
        ];

        $httpClient = new MockHttpClient($responses);
        $connector = new GiteaConnector($httpClient);

        $server = new Server();
        $server->setName('Gitea');
        $server->setHost('gitea.bm-energies.com');
        $server->setPort(3000);

        $integration = new Integration();
        $integration->setType('gitea');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString('504', $result->message);
    }
}
