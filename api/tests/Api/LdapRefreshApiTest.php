<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LocalUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LdapRefreshApiTest extends ApiTestCase
{
    private function createAdminUser(): string
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'admin_refresh@example.com']);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        $user = new LocalUser();
        $user->setEmail('admin_refresh@example.com');
        $user->setUsername('admin_refresh');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $em->persist($user);
        $em->flush();

        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => 'admin_refresh@example.com',
                'password' => 'password',
            ],
        ]);

        return $response->toArray()['token'] ?? '';
    }

    public function testRefreshEndpointIsProtected(): void
    {
        static::createClient()->request('POST', '/api/ldap/users/1/refresh');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshUserNotFound(): void
    {
        $token = $this->createAdminUser();
        static::createClient()->request('POST', '/api/ldap/users/999999/refresh', [
            'auth_bearer' => $token,
        ]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['message' => 'Utilisateur introuvable.']);
    }

    public function testRefreshLocalUserFails(): void
    {
        $token = $this->createAdminUser();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $localUser = $em->getRepository(User::class)->findOneBy(['email' => 'admin_refresh@example.com']);
        $this->assertNotNull($localUser);

        static::createClient()->request('POST', '/api/ldap/users/' . $localUser->getId() . '/refresh', [
            'auth_bearer' => $token,
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['success' => false]);
    }
}
