<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_ADMIN')]
class LdapTestController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $host = $data['host'] ?? null;
        $port = $data['port'] ?? 389;
        $bindDn = $data['bindDn'] ?? null;
        $bindPassword = $data['bindPassword'] ?? null;

        if (!$host) {
            return new JsonResponse(['message' => "L'hôte est requis."], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ldap = Ldap::create('ext_ldap', [
                'host' => $host,
                'port' => (int) $port,
                'encryption' => 'none', // Par défaut pour le test
            ]);

            $ldap->bind($bindDn, $bindPassword);

            return new JsonResponse(['message' => 'Connexion LDAP réussie !']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Échec de la connexion LDAP : '.$e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
