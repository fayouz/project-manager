<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Integration;
use App\Entity\Proxy;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Integration\Connector\MantisConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MantisConnectorTest extends TestCase
{
    public function testSupports(): void
    {
        $connector = new MantisConnector(new MockHttpClient());

        $this->assertTrue($connector->supports('mantis'));
        $this->assertTrue($connector->supports('MANTIS'));
        $this->assertSame('mantis', $connector->getType());
        $this->assertSame('Mantis Bug Tracker', $connector->getName());
        $this->assertFalse($connector->supports('jenkins'));
        $this->assertFalse($connector->supports('gitea'));
    }

    public function testTestConnectionSoapSuccess(): void
    {
        $soapResponseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://futureware.biz/mantisconnect" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <SOAP-ENV:Body>
    <ns1:mc_projects_get_user_accessibleResponse>
      <return SOAP-ENC:arrayType="ns1:ProjectData[2]" xsi:type="SOAP-ENC:Array">
        <item xsi:type="ns1:ProjectData"><id>1</id><name>Project Alpha</name></item>
        <item xsi:type="ns1:ProjectData"><id>2</id><name>Project Beta</name></item>
      </return>
    </ns1:mc_projects_get_user_accessibleResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $mockResponse = new MockResponse($soapResponseXml, [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'text/xml;charset=UTF-8',
                'x-mantis-version' => '2.27.1',
            ],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPort(443);
        $server->setUsername('PINF14');
        $server->setPassword('secret_password');
        $server->setAuthenticationType($authType);
        $server->setOptions(['protocol' => 'https']);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à Mantis', $result->message);
        $this->assertStringContainsString('2.27.1', $result->message);
        $this->assertStringContainsString('PINF14', $result->message);
        $this->assertStringContainsString('2 projets accessibles', $result->message);
        $this->assertSame('2.27.1', $result->details['version'] ?? null);
        $this->assertSame('PINF14', $result->details['username'] ?? null);
        $this->assertSame(2, $result->details['projects_count'] ?? null);
    }

    public function testTestConnectionSoapInvalidCredentials(): void
    {
        $faultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Body>
    <SOAP-ENV:Fault>
      <faultcode>SOAP-ENV:Server</faultcode>
      <faultstring>Error Description: Invalid credentials</faultstring>
    </SOAP-ENV:Fault>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $mockResponse = new MockResponse($faultXml, [
            'http_code' => 500,
            'response_headers' => ['content-type' => 'text/xml;charset=UTF-8'],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setUsername('PINF14');
        $server->setPassword('wrong_password');

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Échec d'authentification Mantis : identifiants invalides", $result->message);
    }

    public function testTestConnectionRestTokenSuccess(): void
    {
        $mockResponse = new MockResponse(
            json_encode([
                'users' => [
                    [
                        'id' => 10,
                        'name' => 'jdoe',
                        'real_name' => 'John Doe',
                        'email' => 'jdoe@example.com',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'application/json',
                    'x-mantis-version' => '2.28.0',
                ],
            ]
        );

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPassword('api_token_value');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Connexion réussie à Mantis', $result->message);
        $this->assertStringContainsString('2.28.0', $result->message);
        $this->assertStringContainsString('jdoe', $result->message);
        $this->assertSame('2.28.0', $result->details['version'] ?? null);
        $this->assertSame('jdoe', $result->details['username'] ?? null);
    }

    public function testTestConnectionRestTokenUnauthorized(): void
    {
        $mockResponse1 = new MockResponse('401 Valid API token required', ['http_code' => 401]);
        $mockResponse2 = new MockResponse('401 Valid API token required', ['http_code' => 401]);

        $httpClient = new MockHttpClient([$mockResponse1, $mockResponse2]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPassword('invalid_token');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Échec d'authentification Mantis (HTTP 401)", $result->message);
    }

    public function testTestConnectionAnonymous(): void
    {
        $versionSoapXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://futureware.biz/mantisconnect">
  <SOAP-ENV:Body>
    <ns1:mc_versionResponse>
      <return xsi:type="xsd:string">2.27.1</return>
    </ns1:mc_versionResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $mockResponse = new MockResponse($versionSoapXml, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'text/xml;charset=UTF-8'],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $server = new Server();
        $server->setName('Anonymous Mantis');
        $server->setHost('mantis.example.com');

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString('Instance Mantis accessible', $result->message);
        $this->assertStringContainsString('2.27.1', $result->message);
        $this->assertStringContainsString('aucun identifiant configuré', $result->message);
    }

    public function testTestConnectionMissingServer(): void
    {
        $connector = new MantisConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('mantis');

        $result = $connector->testConnection($integration);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString("Aucun serveur n'est associé", $result->message);
    }

    public function testGetProjectsSoapSuccess(): void
    {
        $soapResponseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://futureware.biz/mantisconnect" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <SOAP-ENV:Body>
    <ns1:mc_projects_get_user_accessibleResponse>
      <return SOAP-ENC:arrayType="ns1:ProjectData[2]" xsi:type="SOAP-ENC:Array">
        <item xsi:type="ns1:ProjectData">
          <id>10</id>
          <name>Beta Project</name>
          <subprojects>
            <item xsi:type="ns1:ProjectData">
              <id>11</id>
              <name>Sub Beta</name>
            </item>
          </subprojects>
        </item>
        <item xsi:type="ns1:ProjectData">
          <id>1</id>
          <name>Alpha Project</name>
        </item>
      </return>
    </ns1:mc_projects_get_user_accessibleResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $mockResponse = new MockResponse($soapResponseXml, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'text/xml;charset=UTF-8'],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Basic');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setUsername('tester');
        $server->setPassword('secret');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $projects = $connector->getProjects($integration);

        $this->assertCount(3, $projects);
        $this->assertSame('1', $projects[0]['id']);
        $this->assertSame('Alpha Project', $projects[0]['name']);
        $this->assertSame('10', $projects[1]['id']);
        $this->assertSame('Beta Project', $projects[1]['name']);
        $this->assertSame('11', $projects[2]['id']);
        $this->assertSame('Beta Project » Sub Beta', $projects[2]['name']);
    }

    public function testGetProjectsRestSuccess(): void
    {
        $restJson = json_encode([
            'projects' => [
                [
                    'id' => 5,
                    'name' => 'Main Project',
                    'subprojects' => [
                        [
                            'id' => 6,
                            'name' => 'Sub Module',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Another Project',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $mockResponse = new MockResponse($restJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient([$mockResponse]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPassword('my_token');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $projects = $connector->getProjects($integration);

        $this->assertCount(3, $projects);
        $this->assertSame('2', $projects[0]['id']);
        $this->assertSame('Another Project', $projects[0]['name']);
        $this->assertSame('5', $projects[1]['id']);
        $this->assertSame('Main Project', $projects[1]['name']);
        $this->assertSame('6', $projects[2]['id']);
        $this->assertSame('Main Project » Sub Module', $projects[2]['name']);
    }

    public function testGetProjectsThrowsOnMissingServer(): void
    {
        $connector = new MantisConnector(new MockHttpClient());

        $integration = new Integration();
        $integration->setType('mantis');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Aucun serveur n'est associé à cette intégration Mantis.");

        $connector->getProjects($integration);
    }

    public function testCheckHealthWithValidProjectParam(): void
    {
        $restUserJson = json_encode([
            'users' => [
                ['id' => 1, 'name' => 'admin_user'],
            ],
        ], JSON_THROW_ON_ERROR);

        $restProjectsJson = json_encode([
            'projects' => [
                ['id' => 438, 'name' => 'RegazScrapper'],
                ['id' => 10, 'name' => 'Autre Projet'],
            ],
        ], JSON_THROW_ON_ERROR);

        $mockResponseUser = new MockResponse($restUserJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json', 'x-mantis-version' => '2.27.1'],
        ]);
        $mockResponseProjects = new MockResponse($restProjectsJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient([$mockResponseUser, $mockResponseProjects]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPassword('my_token');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $param = new \App\Entity\MantisIntegrationParam();
        $param->setProjectId('438');

        $result = $connector->checkHealth($integration, $param);

        $this->assertTrue($result->success);
        $this->assertSame('healthy', $result->status);
        $this->assertStringContainsString("Projet Mantis 'RegazScrapper' (#438) accessible", $result->message);
        $this->assertSame('RegazScrapper', $param->getProjectName());
        $this->assertSame('RegazScrapper (#438)', $param->getTargetDisplay());
    }

    public function testCheckHealthWithUnknownProjectParam(): void
    {
        $restUserJson = json_encode([
            'users' => [
                ['id' => 1, 'name' => 'admin_user'],
            ],
        ], JSON_THROW_ON_ERROR);

        $restProjectsJson = json_encode([
            'projects' => [
                ['id' => 10, 'name' => 'Autre Projet'],
            ],
        ], JSON_THROW_ON_ERROR);

        $mockResponseUser = new MockResponse($restUserJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
        $mockResponseProjects = new MockResponse($restProjectsJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient([$mockResponseUser, $mockResponseProjects]);
        $connector = new MantisConnector($httpClient);

        $authType = new ServerAuthenticationType();
        $authType->setName('Token');

        $server = new Server();
        $server->setName('Mantis Server');
        $server->setHost('mantis.example.com');
        $server->setPassword('my_token');
        $server->setAuthenticationType($authType);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);

        $param = new \App\Entity\MantisIntegrationParam();
        $param->setProjectId('9999');

        $result = $connector->checkHealth($integration, $param);

        $this->assertFalse($result->success);
        $this->assertSame('error', $result->status);
        $this->assertStringContainsString('Le projet Mantis #9999 est introuvable', $result->message);
    }

    public function testProxyUsedFromIntegrationOverride(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;

            return new MockResponse(json_encode(['version' => '2.27.1'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json', 'x-mantis-version' => '2.27.1'],
            ]);
        });

        $serverProxy = new Proxy();
        $serverProxy->setUrl('http://server-proxy:8080');
        $serverProxy->setEnabled(true);

        $integrationProxy = new Proxy();
        $integrationProxy->setUrl('http://integration-proxy:8080');
        $integrationProxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-mantis.com');
        $server->setProxy($serverProxy);

        $integration = new Integration();
        $integration->setType('mantis');
        $integration->setServer($server);
        $integration->setProxy($integrationProxy);

        $connector = new MantisConnector($httpClient);
        $connector->testConnection($integration);

        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('http://integration-proxy:8080', $capturedOptions['proxy']);
    }
}
