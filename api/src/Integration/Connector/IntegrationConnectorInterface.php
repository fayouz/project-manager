<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Integration\Dto\ConnectionTestResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.integration_connector')]
interface IntegrationConnectorInterface
{
    public function supports(string $type): bool;

    public function getType(): string;

    public function getName(): string;

    public function testConnection(Integration $integration): ConnectionTestResult;

    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult;

    /**
     * Récupère les données réelles (live) de la ressource cible associée.
     *
     * @return array<string, mixed>
     */
    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array;
}
