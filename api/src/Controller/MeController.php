<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MeController extends AbstractController
{
    #[Route('/api/me', name: 'app_api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'type' => $user instanceof LocalUser ? 'local' : 'ldap',
            'isLdap' => $user instanceof LdapUser,
            'image' => $user->getImage(),
            'avatar' => $user->getAvatar(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'displayName' => $user->getDisplayName(),
            'title' => $user->getTitle(),
            'department' => $user->getDepartment(),
            'managerDn' => $user->getManagerDn(),
            'manager' => $user->getManager() ? [
                'id' => $user->getManager()->getId(),
                'email' => $user->getManager()->getEmail(),
                'username' => $user->getManager()->getUsername(),
                'displayName' => $user->getManager()->getDisplayName() ?: ([
                    $user->getManager()->getFirstName(),
                    $user->getManager()->getLastName(),
                ] ? implode(' ', array_filter([$user->getManager()->getFirstName(), $user->getManager()->getLastName()])) : null),
                'avatar' => $user->getManager()->getAvatar(),
            ] : null,
        ]);
    }
}
