<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Entity\Organisation;
use App\Entity\OrganisationMember;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Enum\OrganisationRole;
use App\Enum\ProjectRole;
use App\Enum\TeamRole;
use Doctrine\ORM\EntityManagerInterface;

class TeamAndMembershipApiTest extends ApiTestCase
{
    private EntityManagerInterface $em;
    private LocalUser $localUser;
    private LdapUser $ldapUser;
    private Organisation $organisation;
    private Project $project;

    protected function setUp(): void
    {
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Create a local user
        $this->localUser = new LocalUser();
        $this->localUser->setEmail('local_' . uniqid() . '@example.com');
        $this->localUser->setUsername('local_' . uniqid());
        $this->localUser->setPassword('password123');
        $this->localUser->setRoles(['ROLE_USER']);
        $this->em->persist($this->localUser);

        // Create an LDAP user
        $this->ldapUser = new LdapUser();
        $this->ldapUser->setEmail('ldap_' . uniqid() . '@example.com');
        $this->ldapUser->setUsername('ldap_' . uniqid());
        $this->ldapUser->setLdapUid('uid_' . uniqid());
        $this->ldapUser->setDistinguishedName('cn=ldap,dc=example,dc=com');
        $this->ldapUser->setRoles(['ROLE_USER']);
        $this->em->persist($this->ldapUser);

        // Create an Organisation
        $this->organisation = new Organisation();
        $this->organisation->setName('Org ' . uniqid());
        $this->em->persist($this->organisation);

        // Create a Project
        $this->project = new Project();
        $this->project->setName('Project ' . uniqid());
        $this->project->setOrganisation($this->organisation);
        $this->em->persist($this->project);

        $this->em->flush();
    }

    public function testAddLocalAndLdapUsersToOrganisation(): void
    {
        $client = static::createClient();
        $orgIri = '/api/organisations/' . $this->organisation->getId();
        $localUserIri = '/api/users/' . $this->localUser->getId();
        $ldapUserIri = '/api/users/' . $this->ldapUser->getId();

        // 1. Add Local User as OWNER
        $response = $client->request('POST', '/api/organisation_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'organisation' => $orgIri,
                'user' => $localUserIri,
                'role' => OrganisationRole::OWNER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(OrganisationRole::OWNER->value, $data['role']);
        $this->assertSame('local', $data['user']['type']);
        $this->assertSame($this->localUser->getEmail(), $data['user']['email']);

        // 2. Add LDAP User as MEMBER
        $response = $client->request('POST', '/api/organisation_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'organisation' => $orgIri,
                'user' => $ldapUserIri,
                'role' => OrganisationRole::MEMBER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(OrganisationRole::MEMBER->value, $data['role']);
        $this->assertSame('ldap', $data['user']['type']);
        $this->assertSame($this->ldapUser->getEmail(), $data['user']['email']);

        // 3. Filter organisation members by organisation
        $listResponse = $client->request('GET', '/api/organisation_members?organisation=' . $orgIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $listData = $listResponse->toArray();
        $this->assertCount(2, $listData['member']);
    }

    public function testAddLocalAndLdapUsersToProject(): void
    {
        $client = static::createClient();
        $projectIri = '/api/projects/' . $this->project->getId();
        $localUserIri = '/api/users/' . $this->localUser->getId();
        $ldapUserIri = '/api/users/' . $this->ldapUser->getId();

        // 1. Add Local User as MAINTAINER
        $response = $client->request('POST', '/api/project_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'project' => $projectIri,
                'user' => $localUserIri,
                'role' => ProjectRole::MAINTAINER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(ProjectRole::MAINTAINER->value, $data['role']);
        $this->assertSame($this->localUser->getEmail(), $data['user']['email']);

        // 2. Add LDAP User as DEVELOPER
        $response = $client->request('POST', '/api/project_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'project' => $projectIri,
                'user' => $ldapUserIri,
                'role' => ProjectRole::DEVELOPER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(ProjectRole::DEVELOPER->value, $data['role']);
        $this->assertSame($this->ldapUser->getEmail(), $data['user']['email']);

        // 3. Filter by project
        $listResponse = $client->request('GET', '/api/project_members?project=' . $projectIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $listResponse->toArray()['member']);
    }

    public function testCreateTeamAndAddMembers(): void
    {
        $client = static::createClient();
        $projectIri = '/api/projects/' . $this->project->getId();
        $localUserIri = '/api/users/' . $this->localUser->getId();
        $ldapUserIri = '/api/users/' . $this->ldapUser->getId();

        // 1. Create a Team
        $teamResponse = $client->request('POST', '/api/teams', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Backend Engineers',
                'description' => 'Team in charge of API and integrations',
                'project' => $projectIri,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $teamData = $teamResponse->toArray();
        $teamIri = $teamData['@id'];
        $this->assertSame('Backend Engineers', $teamData['name']);

        // 2. Add Local User as LEAD
        $member1 = $client->request('POST', '/api/team_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'team' => $teamIri,
                'user' => $localUserIri,
                'role' => TeamRole::LEAD->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $m1Data = $member1->toArray();
        $this->assertSame(TeamRole::LEAD->value, $m1Data['role']);
        $this->assertSame($this->localUser->getEmail(), $m1Data['user']['email']);

        // 3. Add LDAP User as MEMBER
        $member2 = $client->request('POST', '/api/team_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'team' => $teamIri,
                'user' => $ldapUserIri,
                'role' => TeamRole::MEMBER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $m2Data = $member2->toArray();
        $this->assertSame(TeamRole::MEMBER->value, $m2Data['role']);
        $this->assertSame($this->ldapUser->getEmail(), $m2Data['user']['email']);

        // 4. Verify team members list
        $membersList = $client->request('GET', '/api/team_members?team=' . $teamIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $membersList->toArray()['member']);

        // 5. Verify teams list by project
        $teamsList = $client->request('GET', '/api/teams?project=' . $projectIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $teamsList->toArray()['member']);
    }

    public function testUpdateAndDeleteMember(): void
    {
        $client = static::createClient();
        $projectIri = '/api/projects/' . $this->project->getId();
        $localUserIri = '/api/users/' . $this->localUser->getId();

        // 1. Add member
        $createResponse = $client->request('POST', '/api/project_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'project' => $projectIri,
                'user' => $localUserIri,
                'role' => ProjectRole::GUEST->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $memberIri = $createResponse->toArray()['@id'];

        // 2. Patch role to MAINTAINER
        $patchResponse = $client->request('PATCH', $memberIri, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'role' => ProjectRole::MAINTAINER->value,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(ProjectRole::MAINTAINER->value, $patchResponse->toArray()['role']);

        // 3. Delete member
        $client->request('DELETE', $memberIri);
        $this->assertResponseStatusCodeSame(204);

        // 4. Verify deleted
        $client->request('GET', $memberIri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDuplicateMembershipThrowsError(): void
    {
        $client = static::createClient();
        $orgIri = '/api/organisations/' . $this->organisation->getId();
        $localUserIri = '/api/users/' . $this->localUser->getId();

        // 1. First add succeeds
        $client->request('POST', '/api/organisation_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'organisation' => $orgIri,
                'user' => $localUserIri,
                'role' => OrganisationRole::MEMBER->value,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // 2. Second add for same user & organisation fails
        $client->request('POST', '/api/organisation_members', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'organisation' => $orgIri,
                'user' => $localUserIri,
                'role' => OrganisationRole::ADMIN->value,
            ],
        ]);
        // Duplicate entry on unique constraint
        $this->assertResponseStatusCodeSame(500);
    }
}
