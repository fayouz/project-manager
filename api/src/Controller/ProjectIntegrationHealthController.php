<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\IntegrationParamFactory;
use App\Entity\ProjectIntegration;
use App\Integration\Dto\ConnectionTestResult;
use App\Integration\IntegrationRegistry;
use App\Repository\ProjectIntegrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ProjectIntegrationHealthController extends AbstractController
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
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
            return new JsonResponse(['message' => 'Liaison projet-intégration introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $integration = $projectIntegration->getIntegration();
        if ($integration === null) {
            return new JsonResponse(['message' => 'Intégration parente introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $type = (string) $integration->getType();
        $connector = $this->registry->getConnector($type);
        if ($connector === null) {
            $result = ConnectionTestResult::failure(
                sprintf("Type d'intégration '%s' non supporté.", $type)
            );
        } else {
            $param = $projectIntegration->getIntegrationParam();
            $result = $connector->checkHealth($integration, $param);

            // Si le paramètre a été enrichi (ex: récupération du nom de projet Mantis)
            if ($param !== null) {
                $projectIntegration->setParameters($param->toArray());
            }
        }

        $projectIntegration->setStatus($result->status);
        $projectIntegration->setStatusMessage($result->message);
        $projectIntegration->setLastCheckedAt($result->testedAt ?? new \DateTimeImmutable());

        $this->entityManager->flush();

        $responsePayload = array_merge($result->toArray(), [
            'targetDisplay' => $projectIntegration->getTargetDisplay(),
            'parameters' => $projectIntegration->getParameters(),
        ]);

        return new JsonResponse($responsePayload, Response::HTTP_OK);
    }
}
