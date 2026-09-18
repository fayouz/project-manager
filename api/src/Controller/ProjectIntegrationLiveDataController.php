<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProjectIntegration;
use App\Integration\IntegrationRegistry;
use App\Repository\ProjectIntegrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ProjectIntegrationLiveDataController extends AbstractController
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly ProjectIntegrationRepository $projectIntegrationRepository,
    ) {
    }

    public function __invoke(Request $request, ?ProjectIntegration $data = null): JsonResponse
    {
        $projectIntegration = $data;
        if ($projectIntegration === null) {
            $id = $request->attributes->get('id');
            if ($id !== null) {
                $projectIntegration = $this->projectIntegrationRepository->find((int) $id);
            }
        }

        if ($projectIntegration === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Liaison projet-intégration introuvable.',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $integration = $projectIntegration->getIntegration();
        if ($integration === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Intégration parente introuvable.',
                'data' => null,
            ], Response::HTTP_BAD_REQUEST);
        }

        $type = (string) $integration->getType();
        $connector = $this->registry->getConnector($type);
        if ($connector === null) {
            return new JsonResponse([
                'success' => false,
                'type' => $type,
                'message' => sprintf("Type d'intégration '%s' non supporté.", $type),
                'data' => null,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $param = $projectIntegration->getIntegrationParam();
            $liveData = $connector->getLiveData($integration, $param);

            return new JsonResponse([
                'success' => true,
                'type' => $type,
                'data' => $liveData,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'type' => $type,
                'message' => $e->getMessage(),
                'data' => null,
            ], Response::HTTP_OK);
        }
    }
}
