<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LdapConfiguration;
use App\Entity\LocalUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LdapConfigurationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'system@example.com']);
        if (!$user) {
            $user = new LocalUser();
            $user->setEmail('system@example.com');
            $user->setUsername('system');
            $user->setPassword('dummy');
            $user->setRoles(['ROLE_USER']);
            $em->persist($user);
            $em->flush();
        }
    }

    private function createAdminUser(): string
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        $user = new LocalUser();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $em->persist($user);
        $em->flush();

        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => 'admin@example.com',
                'password' => 'password',
            ],
        ]);

        return $response->toArray()['token'] ?? '';
    }

    public function testGetLdapConfigurationsIsProtected(): void
    {
        static::createClient()->request('GET', '/api/ldap_configurations');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetLdapConfigurationsAsAdmin(): void
    {
        $token = $this->createAdminUser();

        // S'assurer qu'il y a au moins une config
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.com');
        $config->setBaseDn('dc=example,dc=com');
        $em->persist($config);
        $em->flush();

        static::createClient()->request('GET', '/api/ldap_configurations', [
            'auth_bearer' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testTestLdapConnectionIsProtected(): void
    {
        static::createClient()->request('POST', '/api/ldap_configurations/test', [
            'json' => [
                'host' => 'localhost',
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testTestLdapConnectionAsAdmin(): void
    {
        $token = $this->createAdminUser();
        static::createClient()->request('POST', '/api/ldap_configurations/test', [
            'auth_bearer' => $token,
            'json' => [
                'host' => 'invalid-host',
                'port' => 389,
            ],
        ]);

        // On s'attend à une erreur 400 car l'hôte est invalide, mais pas à une 401
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['message' => "Échec de la connexion LDAP : Can't contact LDAP server"]);
    }

    public function testUpdateLdapConfigurationMapping(): void
    {
        $token = $this->createAdminUser();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.com');
        $config->setBaseDn('dc=example,dc=com');
        $em->persist($config);
        $em->flush();

        $client = static::createClient();
        $client->request('PUT', '/api/ldap_configurations/' . $config->getId(), [
            'auth_bearer' => $token,
            'json' => [
                'host' => 'ldap.example.com',
                'baseDn' => 'dc=example,dc=com',
                'imageAttribute' => 'jpegPhoto',
                'attributeMapping' => [
                    'image' => 'jpegPhoto',
                    'email' => 'mail',
                    'username' => 'sAMAccountName',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'imageAttribute' => 'jpegPhoto',
            'attributeMapping' => [
                'image' => 'jpegPhoto',
                'email' => 'mail',
                'username' => 'sAMAccountName',
            ],
        ]);
    }

    public function testUpdateLdapConfigurationRegexFilters(): void
    {
        $token = $this->createAdminUser();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $config = new LdapConfiguration();
        $config->setHost('ldap.example.com');
        $config->setBaseDn('dc=example,dc=com');
        $em->persist($config);
        $em->flush();

        $client = static::createClient();
        $client->request('PUT', '/api/ldap_configurations/' . $config->getId(), [
            'auth_bearer' => $token,
            'json' => [
                'host' => 'ldap.example.com',
                'baseDn' => 'dc=example,dc=com',
                'searchFilter' => '(sAMAccountName={username})',
                'queryRegex' => '^PINF\\d+$',
                'searchFilterRegex' => '^PINF.*',
                'searchFilters' => [
                    [
                        'attribute' => 'mail',
                        'pattern' => '.*@bm-energies\\.com$',
                    ],
                    [
                        'attribute' => 'sAMAccountName',
                        'pattern' => '^PINF\\d+$',
                    ],
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'searchFilter' => '(sAMAccountName={username})',
            'queryRegex' => '^PINF\\d+$',
            'searchFilterRegex' => '^PINF.*',
            'searchFilters' => [
                [
                    'attribute' => 'mail',
                    'pattern' => '.*@bm-energies\\.com$',
                ],
                [
                    'attribute' => 'sAMAccountName',
                    'pattern' => '^PINF\\d+$',
                ],
            ],
        ]);
    }
}
