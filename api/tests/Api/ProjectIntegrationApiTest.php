<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Integration;
use App\Entity\Organisation;
use App\Entity\Project;
use App\Entity\ProjectIntegration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\ORM\EntityManagerInterface;

class ProjectIntegrationApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em->getRepository(\App\Entity\User::class)->findOneBy([])) {
            $user = new \App\Entity\LocalUser();
            $user->setEmail('test@example.com');
            $user->setUsername('test');
            $user->setPassword('dummy_hashed_password');
            $user->setRoles(['ROLE_ADMIN']);
            $em->persist($user);
            $em->flush();
        }
    }

    public function testGetCollection(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/api/project_integrations', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateProjectIntegrationValidationFailsWithoutRequiredFields(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/project_integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'parameters' => ['repository' => 'owner/repo'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCrudProjectIntegration(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // 1. Create Organization & Project
        $org = new Organisation();
        $org->setName('Test Org ' . uniqid());
        $em->persist($org);

        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setOrganisation($org);
        $em->persist($project);

        // 2. Create Server & Integration
        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if ($serverType->getId() === null) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }

        $authType = $em->getRepository(ServerAuthenticationType::class)->findOneBy([]) ?? new ServerAuthenticationType();
        if ($authType->getId() === null) {
            $authType->setName('Basic');
            $em->persist($authType);
        }

        $server = new Server();
        $server->setName('Server ' . uniqid());
        $server->setHost('gitea.example.com');
        $server->setPort(3000);
        $server->setUsername('test_user');
        $server->setPassword('test_pass');
        $server->setType($serverType);
        $server->setAuthenticationType($authType);
        $em->persist($server);

        $integration = new Integration();
        $integration->setName('Gitea Test ' . uniqid());
        $integration->setType('gitea');
        $integration->setServer($server);
        $em->persist($integration);

        $em->flush();

        $projectIri = '/api/projects/' . $project->getId();
        $integrationIri = '/api/integrations/' . $integration->getId();

        // 3. Create ProjectIntegration
        $createResponse = $client->request('POST', '/api/project_integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'project' => $projectIri,
                'integration' => $integrationIri,
                'parameters' => [
                    'repository' => 'my-org/my-project',
                    'branch' => 'main',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $createdData = $createResponse->toArray();

        $this->assertSame($projectIri, $createdData['project']);
        $this->assertIsArray($createdData['integration']);
        $this->assertSame($integrationIri, $createdData['integration']['@id']);
        $this->assertSame('my-org/my-project', $createdData['parameters']['repository']);
        $this->assertSame('main', $createdData['parameters']['branch']);

        $itemIri = $createdData['@id'];

        // 4. Read single ProjectIntegration
        $getResponse = $client->request('GET', $itemIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $getData = $getResponse->toArray();
        $this->assertSame('my-org/my-project', $getData['parameters']['repository']);
        $this->assertSame('my-org/my-project (main)', $getData['targetDisplay']);

        // 4b. Test Health endpoint on ProjectIntegration
        $healthResponse = $client->request('GET', $itemIri . '/health');
        $this->assertResponseIsSuccessful();
        $healthData = $healthResponse->toArray();
        $this->assertArrayHasKey('success', $healthData);
        $this->assertArrayHasKey('status', $healthData);
        $this->assertSame('my-org/my-project (main)', $healthData['targetDisplay']);

        // 5. Filter by project
        $filterResponse = $client->request('GET', '/api/project_integrations?project=' . $project->getId(), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $filterData = $filterResponse->toArray();
        $this->assertGreaterThanOrEqual(1, $filterData['totalItems']);

        // 6. Update ProjectIntegration parameters
        $patchResponse = $client->request('PATCH', $itemIri, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'parameters' => [
                    'repository' => 'my-org/updated-repo',
                    'branch' => 'develop',
                ],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $patchData = $patchResponse->toArray();
        $this->assertSame('my-org/updated-repo', $patchData['parameters']['repository']);
        $this->assertSame('develop', $patchData['parameters']['branch']);

        // 7. Delete ProjectIntegration
        $client->request('DELETE', $itemIri);
        $this->assertResponseStatusCodeSame(204);

        // Verify deleted
        $client->request('GET', $itemIri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testJenkinsFolderIntegration(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $org = new Organisation();
        $org->setName('Jenkins Test Org ' . uniqid());
        $em->persist($org);

        $project = new Project();
        $project->setName('Jenkins Test Project ' . uniqid());
        $project->setOrganisation($org);
        $em->persist($project);

        $serverType = $em->getRepository(ServerType::class)->findOneBy([]) ?? new ServerType();
        if ($serverType->getId() === null) {
            $serverType->setName('HTTP');
            $em->persist($serverType);
        }

        $server = new Server();
        $server->setName('Jenkins Server ' . uniqid());
        $server->setHost('jenkins.example.com');
        $server->setPort(8080);
        $server->setUsername('jenkins_user');
        $server->setPassword('jenkins_pass');
        $server->setType($serverType);
        $em->persist($server);

        $integration = new Integration();
        $integration->setName('Jenkins CI ' . uniqid());
        $integration->setType('jenkins');
        $integration->setServer($server);
        $em->persist($integration);

        $em->flush();

        $projectIri = '/api/projects/' . $project->getId();
        $integrationIri = '/api/integrations/' . $integration->getId();

        $createResponse = $client->request('POST', '/api/project_integrations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'project' => $projectIri,
                'integration' => $integrationIri,
                'parameters' => [
                    'folder' => 'job/REGAZ/job/RegazScrapper/',
                    'jobs' => [
                        ['name' => '1.1.1_Build_TU', 'status' => 'SUCCESS'],
                        ['name' => '1.1.2_Build_TI', 'status' => 'SUCCESS'],
                    ],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $createdData = $createResponse->toArray();
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $createdData['parameters']['folder']);
        $this->assertSame(2, $createdData['parameters']['jobs_count']);
        $this->assertSame('job/REGAZ/job/RegazScrapper/ (2 jobs)', $createdData['targetDisplay']);
    }
}
