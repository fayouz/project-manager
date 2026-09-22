<?php

declare(strict_types=1);

namespace App\Entity;

final class IntegrationParamFactory
{
    /**
     * @param array<string, mixed> $parameters
     */
    public static function create(string $type, array $parameters = []): IntegrationParamInterface
    {
        return match (strtolower($type)) {
            'mantis' => MantisIntegrationParam::fromArray($parameters),
            'gitea' => GiteaIntegrationParam::fromArray($parameters),
            'sonarqube' => SonarQubeIntegrationParam::fromArray($parameters),
            'jenkins' => JenkinsIntegrationParam::fromArray($parameters),
            'nexus' => NexusIntegrationParam::fromArray($parameters),
            default => new GenericIntegrationParam($type, $parameters),
        };
    }
}
