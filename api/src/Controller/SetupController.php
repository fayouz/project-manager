<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LocalUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    #[Route('/api/install-status', name: 'app_install_status', methods: ['GET'])]
    public function status(UserRepository $userRepository): JsonResponse
    {
        return new JsonResponse([
            'installed' => $userRepository->hasAnyUser(),
        ]);
    }

    #[Route('/api/setup', name: 'app_api_setup', methods: ['POST'])]
    public function setup(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): JsonResponse {
        if ($userRepository->hasAnyUser()) {
            return new JsonResponse(['error' => 'Application already installed'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $confirmPassword = $data['confirmPassword'] ?? null;
        $username = $data['username'] ?? 'root';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        if ($confirmPassword !== null && $confirmPassword !== $password) {
            return new JsonResponse(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters long'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return new JsonResponse(['error' => 'Password must contain at least one uppercase letter, one lowercase letter and one number'], Response::HTTP_BAD_REQUEST);
        }

        $user = new LocalUser();
        $user->setEmail($email);
        $user->setUsername($username ?: 'root');
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Setup completed successfully']);
    }
}
