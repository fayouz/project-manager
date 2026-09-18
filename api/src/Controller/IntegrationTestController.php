<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Integration;
use App\Entity\IntegrationParamFactory;
use App\Entity\Server;
use App\Integration\Dto\ConnectionTestResult;
use App\Integration\IntegrationRegistry;
use App\Repository\IntegrationRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class IntegrationTestController extends AbstractController
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntegrationRepository $integrationRepository,
        private readonly ServerRepository $serverRepository
    ) {
    }

    public function __invoke(Request $request, ?Integration $data = null): JsonResponse
    {
        $id = $request->attributes->get('id');

        // Case 1: Existing integration in database
        if ($id !== null || ($data instanceof Integration && $data->getId() !== null)) {
            $integration = $data instanceof Integration && $data->getId() !== null
                ? $data
                : $this->integrationRepository->find((int) $id);

            if ($integration === null) {
                return new JsonResponse(['message' => 'Intégration introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $connector = $this->registry->getConnector((string) $integration->getType());
            if ($connector === null) {
                $result = ConnectionTestResult::failure(
                    sprintf("Type d'intégration '%s' non supporté.", $integration->getType())
                );
            } else {
                $payload = json_decode($request->getContent(), true);
                $parameters = is_array($payload) && isset($payload['parameters']) && is_array($payload['parameters'])
                    ? $payload['parameters']
                    : null;

                if ($parameters !== null) {
                    $param = IntegrationParamFactory::create((string) $integration->getType(), $parameters);
                    $result = $connector->checkHealth($integration, $param);
                } else {
                    $result = $connector->testConnection($integration);
                }
            }

            $integration->setStatus($result->status);
            $integration->setStatusMessage($result->message);
            $integration->setLastCheckedAt($result->testedAt ?? new \DateTimeImmutable());

            $this->entityManager->flush();

            return new JsonResponse($result->toArray(), Response::HTTP_OK);
        }

        // Case 2: Transient test on-the-fly before creation
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $type = $payload['type'] ?? null;
        if (empty($type) || !is_string($type)) {
            return new JsonResponse(['message' => "Le type d'intégration est requis."], Response::HTTP_BAD_REQUEST);
        }

        $connector = $this->registry->getConnector($type);
        if ($connector === null) {
            return new JsonResponse(
                ConnectionTestResult::failure(sprintf("Type d'intégration '%s' non supporté.", $type))->toArray(),
                Response::HTTP_BAD_REQUEST
            );
        }

        $transient = new Integration();
        $transient->setType($type);

        if (empty($payload['server'])) {
            return new JsonResponse(['message' => "Le serveur est requis pour tester l'intégration."], Response::HTTP_BAD_REQUEST);
        }

        $serverVal = $payload['server'];
        $serverId = null;
        if (is_numeric($serverVal)) {
            $serverId = (int) $serverVal;
        } elseif (is_string($serverVal) && preg_match('#/api/servers/(\d+)#', $serverVal, $matches)) {
            $serverId = (int) $matches[1];
        }

        if ($serverId === null) {
            return new JsonResponse(['message' => "Identifiant de serveur invalide."], Response::HTTP_BAD_REQUEST);
        }

        $server = $this->serverRepository->find($serverId);
        if ($server === null) {
            return new JsonResponse(['message' => "Serveur introuvable."], Response::HTTP_BAD_REQUEST);
        }

        $transient->setServer($server);

        $result = $connector->testConnection($transient);

        return new JsonResponse($result->toArray(), Response::HTTP_OK);
    }
}
