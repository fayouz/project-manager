<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Integration;
use App\Integration\Connector\ProjectProviderConnectorInterface;
use App\Integration\IntegrationRegistry;
use App\Repository\IntegrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class IntegrationProjectsController extends AbstractController
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly IntegrationRepository $integrationRepository,
    ) {
    }

    public function __invoke(Request $request, ?Integration $data = null): JsonResponse
    {
        $integration = $data;
        if ($integration === null) {
            $id = $request->attributes->get('id');
            if ($id !== null) {
                $integration = $this->integrationRepository->find((int) $id);
            }
        }

        if ($integration === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Intégration introuvable.',
                'projects' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        $type = $integration->getType();
        if ($type === null || $type === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Type d\'intégration non défini.',
                'projects' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $connector = $this->registry->getConnector($type);
        if ($connector === null) {
            return new JsonResponse([
                'success' => false,
                'message' => sprintf('Aucun connecteur disponible pour le type "%s".', $type),
                'projects' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$connector instanceof ProjectProviderConnectorInterface) {
            return new JsonResponse([
                'success' => false,
                'message' => sprintf('Le service "%s" ne supporte pas la liste dynamique des projets.', $connector->getName()),
                'projects' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $folder = $request->query->get('folder') ?? $request->query->get('path');
        if ($folder !== null && trim((string) $folder) !== '' && method_exists($connector, 'fetchJobs')) {
            try {
                $jobs = $connector->fetchJobs($integration, (string) $folder);

                return new JsonResponse([
                    'success' => true,
                    'folder' => (string) $folder,
                    'jobs' => $jobs,
                    'count' => count($jobs),
                ], Response::HTTP_OK);
            } catch (\Throwable $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'jobs' => [],
                ], Response::HTTP_BAD_GATEWAY);
            }
        }

        try {
            $projects = $connector->getProjects($integration);

            return new JsonResponse([
                'success' => true,
                'projects' => $projects,
                'count' => count($projects),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'projects' => [],
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
