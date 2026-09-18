<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LocalUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthAndRegistrationTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();

        // Initialiser le premier compte superadmin (Setup initial)
        $rootUser = new LocalUser();
        $rootUser->setEmail('root@example.com');
        $rootUser->setUsername('root');
        $rootUser->setPassword('dummy_hashed_password');
        $rootUser->setRoles(['ROLE_SUPER_ADMIN']);
        $this->entityManager->persist($rootUser);
        $this->entityManager->flush();
    }

    public function testRegisterAndLoginWorkflow(): void
    {
        $client = static::createClient();

        // 1. Inscription d'un utilisateur local
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => 'developer@example.com',
                'password' => 'Password123',
                'username' => 'devuser',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains(['email' => 'developer@example.com']);

        // 2. Échec de connexion avec mot de passe erroné
        $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => 'developer@example.com',
                'password' => 'BadPassword',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // 3. Connexion réussie avec le bon mot de passe
        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => 'developer@example.com',
                'password' => 'Password123',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
        $token = $data['token'];

        // 4. Appel à /api/me avec le Bearer token
        $client->request('GET', '/api/me', [
            'auth_bearer' => $token,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'email' => 'developer@example.com',
            'username' => 'devuser',
            'type' => 'local',
            'isLdap' => false,
        ]);
    }

    public function testDuplicateEmailRegistration(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [
            'json' => [
                'email' => 'unique@example.com',
                'password' => 'Password123',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('POST', '/api/register', [
            'json' => [
                'email' => 'unique@example.com',
                'password' => 'AnotherPassword123',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }
}
