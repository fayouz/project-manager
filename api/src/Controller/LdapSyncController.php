<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LdapSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class LdapSyncController extends AbstractController
{
    #[Route('/api/ldap/sync', name: 'app_ldap_sync', methods: ['POST'])]
    public function sync(LdapSyncService $ldapSyncService): JsonResponse
    {
        $result = $ldapSyncService->syncUsers();

        if (!$result['success']) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
