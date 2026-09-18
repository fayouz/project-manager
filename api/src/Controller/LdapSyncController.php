<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LdapUser;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class LdapSyncController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/ldap/sync', name: 'app_ldap_sync', methods: ['POST'])]
    public function sync(LdapSyncService $ldapSyncService): JsonResponse
    {
        $result = $ldapSyncService->syncUsers();

        if (!$result['success']) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/api/ldap/users/{id}/refresh', name: 'app_ldap_user_refresh', methods: ['POST'])]
    #[Route('/api/users/{id}/ldap-refresh', name: 'app_user_ldap_refresh', methods: ['POST'])]
    public function refreshUser(
        int $id,
        UserRepository $userRepository,
        LdapSyncService $ldapSyncService,
        SerializerInterface $serializer,
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
        }

        if (!$user instanceof LdapUser) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Seuls les comptes synchronisés via LDAP peuvent être rafraîchis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $ldapSyncService->refreshUser($user);
        if (!$result['success']) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        $userData = json_decode($serializer->serialize($user, 'json', ['groups' => ['user:read']]), true);
        $result['user'] = $userData;

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
