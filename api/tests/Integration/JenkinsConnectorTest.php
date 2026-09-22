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

    public function testGetProjects(): void
    {
        $mockData = [
            'jobs' => [
                [
                    'name' => 'REGAZ',
                    '_class' => 'com.cloudbees.hudson.plugins.folder.Folder',
                    'jobs' => [
                        [
                            'name' => 'RegazScrapper',
                            '_class' => 'com.cloudbees.hudson.plugins.folder.Folder',
                            'jobs' => [
                                ['name' => 'job_inside', '_class' => 'org.jenkinsci.plugins.workflow.job.WorkflowJob']
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'StandaloneJob',
                    '_class' => 'hudson.model.FreeStyleProject'
                ]
            ]
        ];

        $mockResponse = new MockResponse(json_encode($mockData, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json']
        ]);

        $server = new Server();
        $server->setHost('jenkins.example.com');
        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $connector = new JenkinsConnector(new MockHttpClient([$mockResponse]));
        $folders = $connector->getProjects($integration);

        $this->assertCount(2, $folders);
        $this->assertSame('job/REGAZ/', $folders[0]['id']);
        $this->assertSame('REGAZ', $folders[0]['name']);
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $folders[1]['id']);
        $this->assertSame('REGAZ » RegazScrapper', $folders[1]['name']);
    }

    public function testFetchJobsAndCheckHealthWithFolder(): void
    {
        $mockFolderData = [
            'name' => 'RegazScrapper',
            'jobs' => [
                [
                    'name' => '1.1.1_RegazScrapper_Build_TU',
                    'url' => 'http://jenkins.example.com/job/REGAZ/job/RegazScrapper/job/1.1.1_RegazScrapper_Build_TU/',
                    'color' => 'blue',
                    '_class' => 'org.jenkinsci.plugins.workflow.job.WorkflowJob',
                    'lastBuild' => [
                        'number' => 147,
                        'url' => 'http://jenkins.example.com/job/REGAZ/job/RegazScrapper/job/1.1.1_RegazScrapper_Build_TU/147/',
                        'result' => 'SUCCESS',
                        'timestamp' => 1726000000000,
                        'duration' => 65000,
                        'building' => false,
                    ]
                ],
                [
                    'name' => '1.1.2_RegazScrapper_Build_TI',
                    'url' => 'http://jenkins.example.com/job/REGAZ/job/RegazScrapper/job/1.1.2_RegazScrapper_Build_TI/',
                    'color' => 'red',
                    '_class' => 'org.jenkinsci.plugins.workflow.job.WorkflowJob',
                    'lastBuild' => [
                        'number' => 148,
                        'url' => 'http://jenkins.example.com/job/REGAZ/job/RegazScrapper/job/1.1.2_RegazScrapper_Build_TI/148/',
                        'result' => 'FAILURE',
                        'timestamp' => 1726001000000,
                        'duration' => 120000,
                        'building' => false,
                    ]
                ]
            ]
        ];

        $server = new Server();
        $server->setHost('jenkins.example.com');
        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        // Test fetchJobs
        $mockResponse1 = new MockResponse(json_encode($mockFolderData, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json']
        ]);
        $connector = new JenkinsConnector(new MockHttpClient([$mockResponse1]));
        $jobs = $connector->fetchJobs($integration, 'REGAZ/RegazScrapper');

        $this->assertCount(2, $jobs);
        $this->assertSame('1.1.1_RegazScrapper_Build_TU', $jobs[0]['name']);
        $this->assertSame('SUCCESS', $jobs[0]['status']);
        $this->assertSame('success', $jobs[0]['statusBadgeColor']);
        $this->assertSame('1.1.2_RegazScrapper_Build_TI', $jobs[1]['name']);
        $this->assertSame('FAILURE', $jobs[1]['status']);
        $this->assertSame('error', $jobs[1]['statusBadgeColor']);

        // Test checkHealth
        $testConnResponse = new MockResponse(json_encode(['_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json', 'x-jenkins' => '2.440.1']
        ]);
        $folderResponse = new MockResponse(json_encode($mockFolderData, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json']
        ]);

        $connector2 = new JenkinsConnector(new MockHttpClient([$testConnResponse, $folderResponse]));
        $param = \App\Entity\JenkinsIntegrationParam::fromArray(['folder' => 'job/REGAZ/job/RegazScrapper/']);

        $health = $connector2->checkHealth($integration, $param);
        $this->assertTrue($health->success);
        $this->assertSame('healthy', $health->status);
        $this->assertStringContainsString('2 job(s) référencé(s)', $health->message);

        // Test getLiveData
        $folderResponse2 = new MockResponse(json_encode($mockFolderData, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json']
        ]);
        $connector3 = new JenkinsConnector(new MockHttpClient([$folderResponse2]));
        $liveData = $connector3->getLiveData($integration, $param);

        $this->assertTrue($liveData['connected']);
        $this->assertSame('jenkins', $liveData['type']);
        $this->assertSame(2, $liveData['stats']['total']);
        $this->assertSame(1, $liveData['stats']['success']);
        $this->assertSame(1, $liveData['stats']['failure']);
        $this->assertSame('1.1.2_RegazScrapper_Build_TI', $liveData['lastBuild']['jobName']);
    }

    public function testProxyBypassedForInternalHost(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;
            return new MockResponse(json_encode(['_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['x-jenkins' => '2.401.2'],
            ]);
        });

        $server = new Server();
        $server->setHost('jenkins.bm-energies.com');
        $server->setOptions(['protocol' => 'http']);

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $connector = new JenkinsConnector($httpClient);
        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('', $capturedOptions['proxy']);
    }

    public function testProxyBypassedWhenDirectOption(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;
            return new MockResponse(json_encode(['_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['x-jenkins' => '2.401.2'],
            ]);
        });

        $server = new Server();
        $server->setHost('jenkins.external-cloud.com');
        $server->setOptions(['protocol' => 'https', 'proxy' => 'direct']);

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $connector = new JenkinsConnector($httpClient);
        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('', $capturedOptions['proxy']);
    }

    public function testProxyUsedWhenExplicitlyConfigured(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;
            return new MockResponse(json_encode(['_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['x-jenkins' => '2.401.2'],
            ]);
        });

        $server = new Server();
        $server->setHost('jenkins.external-cloud.com');
        $server->setOptions(['protocol' => 'https', 'proxy' => 'http://corporate-proxy:8080']);

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        $connector = new JenkinsConnector($httpClient);
        $result = $connector->testConnection($integration);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('proxy', $capturedOptions);
        $this->assertSame('http://corporate-proxy:8080', $capturedOptions['proxy']);
    }

    public function testProxyBypassedWhenExternalHostInNoProxy(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;
            return new MockResponse(json_encode(['_class' => 'hudson.model.Hudson'], JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['x-jenkins' => '2.401.2'],
            ]);
        });

        $_SERVER['NO_PROXY'] = 'mycompany.org,.internal.net';

        $server = new Server();
        $server->setHost('jenkins.mycompany.org');
        $server->setOptions(['protocol' => 'https']);

        $integration = new Integration();
        $integration->setType('jenkins');
        $integration->setServer($server);

        try {
            $connector = new JenkinsConnector($httpClient);
            $result = $connector->testConnection($integration);

            $this->assertTrue($result->success);
            $this->assertArrayHasKey('proxy', $capturedOptions);
            $this->assertSame('', $capturedOptions['proxy']);
        } finally {
            unset($_SERVER['NO_PROXY']);
        }
    }
}
