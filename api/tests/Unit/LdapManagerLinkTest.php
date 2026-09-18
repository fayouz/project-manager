<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\LdapConfiguration;
use App\Entity\LdapUser;
use App\Repository\LdapConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;

class LdapManagerLinkTest extends TestCase
{
    public function testFindOrCreateManagerFromDnReturnsExistingUser(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $existingManager = new LdapUser();
        $existingManager->setUsername('mgr01');
        $existingManager->setDistinguishedName('cn=Boss,ou=users,dc=example,dc=org');

        $ldapUserRepo = $this->createMock(EntityRepository::class);
        $ldapUserRepo->method('findOneBy')
            ->with(['distinguishedName' => 'cn=Boss,ou=users,dc=example,dc=org'])
            ->willReturn($existingManager);

        $em->method('getRepository')
            ->with(LdapUser::class)
            ->willReturn($ldapUserRepo);

        $service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);

        $manager = $service->findOrCreateManagerFromDn('cn=Boss,ou=users,dc=example,dc=org');

        $this->assertSame($existingManager, $manager);
    }

    public function testFindOrCreateManagerFromDnQueriesLdapAndCreatesNewManager(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null); // Pas d'utilisateur existant par email ou username

        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $ldapUserRepo = $this->createMock(EntityRepository::class);
        $ldapUserRepo->method('findOneBy')->willReturn(null);

        $em->method('getRepository')
            ->with(LdapUser::class)
            ->willReturn($ldapUserRepo);

        $persisted = [];
        $em->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $managerEntry = new Entry('cn=Super Manager,ou=users,dc=example,dc=org', [
            'sAMAccountName' => ['MGR99'],
            'mail' => ['super.manager@example.org'],
            'givenName' => ['Super'],
            'sn' => ['Manager'],
            'displayName' => ['Super Manager'],
            'title' => ['Directeur Général'],
            'department' => ['Direction'],
        ]);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$managerEntry]));

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->method('execute')->willReturn($collection);

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->expects($this->once())
            ->method('query')
            ->with('cn=Super Manager,ou=users,dc=example,dc=org', '(objectClass=*)', ['scope' => QueryInterface::SCOPE_BASE])
            ->willReturn($mockQuery);

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct($c, $u, $e, $l, private readonly LdapInterface $mockLdap)
            {
                parent::__construct($c, $u, $e, $l);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $manager = $service->findOrCreateManagerFromDn('cn=Super Manager,ou=users,dc=example,dc=org', $config);

        $this->assertInstanceOf(LdapUser::class, $manager);
        $this->assertSame('MGR99', $manager->getUsername());
        $this->assertSame('super.manager@example.org', $manager->getEmail());
        $this->assertSame('Super Manager', $manager->getDisplayName());
        $this->assertSame('Directeur Général', $manager->getTitle());
        $this->assertSame('Direction', $manager->getDepartment());
        $this->assertSame('cn=Super Manager,ou=users,dc=example,dc=org', $manager->getDistinguishedName());
        $this->assertCount(1, $persisted);
        $this->assertSame($manager, $persisted[0]);
    }

    public function testFindOrCreateManagerFromDnCreatesPlaceholderWhenLdapFails(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $ldapUserRepo = $this->createMock(EntityRepository::class);
        $ldapUserRepo->method('findOneBy')->willReturn(null);
        $em->method('getRepository')->with(LdapUser::class)->willReturn($ldapUserRepo);

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->method('query')->willThrowException(new \RuntimeException('Connection failed'));

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct($c, $u, $e, $l, private readonly LdapInterface $mockLdap)
            {
                parent::__construct($c, $u, $e, $l);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $manager = $service->findOrCreateManagerFromDn('cn=Jane Boss,ou=users,dc=example,dc=org', $config);

        $this->assertInstanceOf(LdapUser::class, $manager);
        $this->assertSame('Jane Boss', $manager->getUsername());
        $this->assertSame('Jane Boss', $manager->getDisplayName());
        $this->assertSame('cn=Jane Boss,ou=users,dc=example,dc=org', $manager->getDistinguishedName());
        $this->assertStringStartsWith('janeboss@ldap.local', $manager->getEmail());
    }

    public function testFindOrCreateManagerFromDnPreventsCircularRecursion(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);

        $visitedDns = ['cn=circular,ou=users,dc=example,dc=org'];
        $result = $service->findOrCreateManagerFromDn('CN=Circular,ou=users,dc=example,dc=org', null, null, $visitedDns);

        $this->assertNull($result);
    }
}
