<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\LdapConfiguration;
use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Repository\LdapConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;

class LdapUserRefreshTest extends TestCase
{
    public function testRefreshRejectsLocalUser(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);

        $localUser = new LocalUser();
        $localUser->setEmail('local@example.org');

        $result = $service->refreshUser($localUser);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Seuls les comptes synchronisés via LDAP', $result['message']);
    }

    public function testRefreshFailsWhenNoActiveConfiguration(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $ldapConfigRepo->method('findOneBy')->willReturn(null);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);

        $ldapUser = new LdapUser();
        $ldapUser->setEmail('ldap@example.org');

        $result = $service->refreshUser($ldapUser);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Aucune configuration LDAP valide trouvée', $result['message']);
    }

    public function testRefreshUpdatesEmailAndImageExceptUsername(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $config->setImageAttribute('jpegPhoto');
        $config->setEmailAttribute('mail');
        $config->setUsernameAttribute('sAMAccountName');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null); // Pas de conflit email
        $em = $this->createMock(EntityManagerInterface::class);
        $ldapUserRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $ldapUserRepo->method('findOneBy')->willReturn(null);
        $em->method('getRepository')->willReturn($ldapUserRepo);
        $logger = $this->createMock(LoggerInterface::class);

        $jpegBinary = "\xFF\xD8\xFF\xE0\x00\x10JFIF" . str_repeat('X', 30);
        $entry = new Entry('cn=john,ou=people,dc=example,dc=org', [
            'sAMAccountName' => ['new_ldap_samaccountname'],
            'mail' => ['john.updated@example.org'],
            'jpegPhoto' => [$jpegBinary],
            'givenName' => ['John'],
            'sn' => ['Doe'],
            'displayName' => ['John Doe'],
            'title' => ['Lead Developer'],
            'department' => ['R&D'],
            'manager' => ['cn=boss,ou=people,dc=example,dc=org'],
        ]);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->method('execute')->willReturn($collection);

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->method('escape')->willReturnArgument(0);
        // La première requête cherche l'utilisateur par cn, la seconde cherche le manager par son DN
        $mockLdap->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $dn, string $query, array $options = []) use ($mockQuery) {
                return $mockQuery;
            });

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct(
                $ldapConfigRepo,
                $userRepo,
                $em,
                $logger,
                private readonly LdapInterface $mockLdap
            ) {
                parent::__construct($ldapConfigRepo, $userRepo, $em, $logger);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $user = new LdapUser();
        $user->setUsername('original_samaccountname');
        $user->setLdapUid('original_samaccountname');
        $user->setEmail('john.old@example.org');
        $user->setImage(null);

        $result = $service->refreshUser($user, $config);

        $this->assertTrue($result['success']);
        // Email mis à jour
        $this->assertSame('john.updated@example.org', $user->getEmail());
        // Image mise à jour en base64
        $this->assertSame(base64_encode($jpegBinary), $user->getImage());
        $this->assertStringStartsWith('data:image/jpeg;base64,', (string) $user->getAvatar());
        // Métadonnées LDAP extraites
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('John Doe', $user->getDisplayName());
        $this->assertSame('Lead Developer', $user->getTitle());
        $this->assertSame('R&D', $user->getDepartment());
        $this->assertSame('cn=boss,ou=people,dc=example,dc=org', $user->getManagerDn());
        // sAMAccountName / username ABSOLUMENT NON MODIFIÉ (préservé tel quel)
        $this->assertSame('original_samaccountname', $user->getUsername());
        $this->assertSame('original_samaccountname', $user->getLdapUid());
        // DN et syncedAt mis à jour
        $this->assertSame('cn=john,ou=people,dc=example,dc=org', $user->getDistinguishedName());
        $this->assertNotNull($user->getSyncedAt());
        // Manager créé et lié
        $this->assertNotNull($user->getManager());
        $this->assertInstanceOf(LdapUser::class, $user->getManager());
    }

    public function testRefreshUserFailsWhenEmailConflicts(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $conflictUser = new LocalUser();
        // Simuler un ID différent via réflexion
        $ref = new \ReflectionProperty(LocalUser::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($conflictUser, 999);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn($conflictUser);

        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entry = new Entry('cn=john,dc=example,dc=org', [
            'mail' => ['taken@example.org'],
            'sAMAccountName' => ['john'],
        ]);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->method('execute')->willReturn($collection);

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->method('escape')->willReturnArgument(0);
        $mockLdap->method('query')->willReturn($mockQuery);

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct(
                $ldapConfigRepo,
                $userRepo,
                $em,
                $logger,
                private readonly LdapInterface $mockLdap
            ) {
                parent::__construct($ldapConfigRepo, $userRepo, $em, $logger);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $user = new LdapUser();
        $ref->setValue($user, 1);
        $user->setEmail('old@example.org');
        $user->setUsername('john');

        $result = $service->refreshUser($user, $config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('déjà utilisée', $result['message']);
        $this->assertSame('old@example.org', $user->getEmail());
    }

    public function testRefreshUsesConfiguredSearchFilterTemplate(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $config->setSearchFilter('(sAMAccountName={username})');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entry = new Entry('cn=PINF14,ou=users,dc=example,dc=org', [
            'sAMAccountName' => ['PINF14'],
            'mail' => ['pinf14@example.org'],
            'displayName' => ['PINF14 User'],
        ]);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->method('execute')->willReturn($collection);

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->method('escape')->willReturnArgument(0);
        // Vérifie que la recherche utilise bien le template configuré
        $mockLdap->expects($this->once())
            ->method('query')
            ->with('dc=example,dc=org', '(sAMAccountName=PINF14)')
            ->willReturn($mockQuery);

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct(
                $ldapConfigRepo,
                $userRepo,
                $em,
                $logger,
                private readonly LdapInterface $mockLdap
            ) {
                parent::__construct($ldapConfigRepo, $userRepo, $em, $logger);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $user = new LdapUser();
        $user->setUsername('PINF14');
        $user->setLdapUid('PINF14');
        $user->setEmail('old@example.org');

        $result = $service->refreshUser($user, $config);

        $this->assertTrue($result['success']);
        $this->assertSame('pinf14@example.org', $user->getEmail());
        $this->assertSame('PINF14 User', $user->getDisplayName());
    }

    public function testRefreshRejectsWhenUserDoesNotMatchQueryRegex(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $config->setQueryRegex('^PINF\\d+$');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $mockLdap = $this->createMock(LdapInterface::class);

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct(
                $ldapConfigRepo,
                $userRepo,
                $em,
                $logger,
                private readonly LdapInterface $mockLdap
            ) {
                parent::__construct($ldapConfigRepo, $userRepo, $em, $logger);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $user = new LdapUser();
        $user->setUsername('OTHER_USER');
        $user->setLdapUid('OTHER_USER');
        $user->setEmail('other@example.org');

        $result = $service->refreshUser($user, $config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ne correspond pas à la regex de requête', $result['message']);
    }

    public function testRefreshFallbackToDirectDnReadWhenSearchFails(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.org');
        $config->setBaseDn('dc=example,dc=org');
        $ldapConfigRepo->method('findOneBy')->willReturn($config);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $dn = 'cn=PINF14,ou=users,dc=example,dc=org';
        $entry = new Entry($dn, [
            'sAMAccountName' => ['PINF14'],
            'mail' => ['recovered@example.org'],
            'displayName' => ['Recovered User'],
        ]);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $fallbackQuery = $this->createMock(QueryInterface::class);
        $fallbackQuery->method('execute')->willReturn($collection);

        $failingQuery = $this->createMock(QueryInterface::class);
        $failingQuery->method('execute')->willThrowException(new \RuntimeException('LDAP error [1] Operations error'));

        $mockLdap = $this->createMock(LdapInterface::class);
        $mockLdap->method('escape')->willReturnArgument(0);
        $mockLdap->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $dnArg, string $filterArg, array $optionsArg) use ($dn, $failingQuery, $fallbackQuery) {
                if ($dnArg === 'dc=example,dc=org') {
                    return $failingQuery;
                }
                if ($dnArg === $dn && ($optionsArg['scope'] ?? null) === QueryInterface::SCOPE_BASE) {
                    return $fallbackQuery;
                }
                throw new \InvalidArgumentException('Unexpected query call: ' . $dnArg);
            });

        $service = new class($ldapConfigRepo, $userRepo, $em, $logger, $mockLdap) extends LdapSyncService {
            public function __construct(
                $ldapConfigRepo,
                $userRepo,
                $em,
                $logger,
                private readonly LdapInterface $mockLdap
            ) {
                parent::__construct($ldapConfigRepo, $userRepo, $em, $logger);
            }

            protected function createLdapClient(LdapConfiguration $config): LdapInterface
            {
                return $this->mockLdap;
            }
        };

        $user = new LdapUser();
        $user->setUsername('PINF14');
        $user->setLdapUid('PINF14');
        $user->setDistinguishedName($dn);
        $user->setEmail('old@example.org');

        $result = $service->refreshUser($user, $config);

        $this->assertTrue($result['success']);
        $this->assertSame('recovered@example.org', $user->getEmail());
        $this->assertSame('Recovered User', $user->getDisplayName());
    }
}
