<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Organisation;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class StagingApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em->getRepository(\App\Entity\User::class)->findOneBy([])) {
            $user = new \App\Entity\LocalUser();
            $user->setEmail('staging_test@example.com');
            $user->setUsername('staging_test');
            $user->setPassword('dummy_hashed_password');
            $user->setRoles(['ROLE_ADMIN']);
            $em->persist($user);
            $em->flush();
        }
    }

    public function testCrudDeploymentServerAndStaging(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // 1. Create Organization and Project
        $org = new Organisation();
        $org->setName('Test Org ' . uniqid());
        $em->persist($org);

        $project = new Project();
        $project->setName('Test Project Staging ' . uniqid());
        $project->setOrganisation($org);
        $em->persist($project);
        $em->flush();

        $projectIri = '/api/projects/' . $project->getId();

        // 2. Create DeploymentServer with webserverUrl
        $serverPayload = [
            'name' => 'Prod Cluster 01',
            'webserverUrl' => 'https://staging.example.com',
            'host' => '192.168.1.100',
            'port' => 443,
            'description' => 'Main staging server for web apps',
        ];

        $serverRes = $client->request('POST', '/api/deployment_servers', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => $serverPayload,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $serverData = $serverRes->toArray();
        $this->assertSame('Prod Cluster 01', $serverData['name']);
        $this->assertSame('https://staging.example.com', $serverData['webserverUrl']);
        $serverIri = $serverData['@id'];

        // 3. Create Staging linked to Project and DeploymentServer
        $stagingPayload = [
            'name' => 'Staging Environment',
            'project' => $projectIri,
            'deploymentServer' => $serverIri,
            'environment' => 'staging',
            'status' => 'active',
            'branch' => 'main',
        ];

        $stagingRes = $client->request('POST', '/api/stagings', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => $stagingPayload,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $stagingData = $stagingRes->toArray();
        $this->assertSame('Staging Environment', $stagingData['name']);
        $this->assertSame($projectIri, $stagingData['project']);
        $this->assertSame($serverIri, $stagingData['deploymentServer']);
        $stagingIri = $stagingData['@id'];

        // 4. Retrieve Project Staging list & filter by project
        $getRes = $client->request('GET', $stagingIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $getData = $getRes->toArray();
        $this->assertSame('Staging Environment', $getData['name']);

        $filterRes = $client->request('GET', '/api/stagings?project=' . urlencode($projectIri), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $filterData = $filterRes->toArray();
        $this->assertCount(1, $filterData['member']);
        $this->assertSame($stagingIri, $filterData['member'][0]['@id']);

        // 5. Update Staging
        $patchRes = $client->request('PATCH', $stagingIri, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Staging Updated',
                'status' => 'deployed',
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Staging Updated', $patchRes->toArray()['name']);
        $this->assertSame('deployed', $patchRes->toArray()['status']);

        // 6. Delete Staging
        $client->request('DELETE', $stagingIri);
        $this->assertResponseStatusCodeSame(204);

        // 7. Delete DeploymentServer
        $client->request('DELETE', $serverIri);
        $this->assertResponseStatusCodeSame(204);
    }
}
