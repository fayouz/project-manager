<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LocalUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class SetupRedirectionTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->clearUsers();
    }

    protected function tearDown(): void
    {
        $this->clearUsers();
    }

    private function clearUsers(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    private function createSampleUser(): void
    {
        $user = new LocalUser();
        $user->setEmail('root@example.com');
        $user->setUsername('root');
        $user->setPassword('dummy_hashed_password');
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function testInstallStatusWhenNotInstalled(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/install-status');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['installed' => false]);
    }

    public function testSetupCreationWhenNotInstalled(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup', [
            'json' => [
                'email' => 'superadmin@example.com',
                'password' => 'Password123',
                'username' => 'root',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['message' => 'Setup completed successfully']);

        // Vérifier que le statut passe à installed: true
        $client->request('GET', '/api/install-status');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['installed' => true]);
    }

    public function testSetupForbiddenWhenAlreadyInstalled(): void
    {
        $this->createSampleUser();

        $client = static::createClient();
        $client->request('GET', '/api/install-status');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['installed' => true]);

        $client->request('POST', '/api/setup', [
            'json' => [
                'email' => 'other@example.com',
                'password' => 'Password123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertJsonContains(['error' => 'Application already installed']);
    }

    public function testSetupPasswordMismatch(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup', [
            'json' => [
                'email' => 'superadmin@example.com',
                'password' => 'Password123',
                'confirmPassword' => 'Different123',
                'username' => 'root',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJsonContains(['error' => 'Passwords do not match']);
    }

    public function testSetupWithMatchingConfirmPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup', [
            'json' => [
                'email' => 'superadmin@example.com',
                'password' => 'Password123',
                'confirmPassword' => 'Password123',
                'username' => 'root',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['message' => 'Setup completed successfully']);
    }
}
