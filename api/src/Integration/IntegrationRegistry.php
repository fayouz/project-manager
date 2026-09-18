<?php

declare(strict_types=1);

namespace App\Integration;

use App\Integration\Connector\IntegrationConnectorInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class IntegrationRegistry
{
    /**
     * @var iterable<IntegrationConnectorInterface>
     */
    private iterable $connectors;

    /**
     * @param iterable<IntegrationConnectorInterface> $connectors
     */
    public function __construct(
        #[TaggedIterator('app.integration_connector')] iterable $connectors
    ) {
        $this->connectors = $connectors;
    }

    public function getConnector(string $type): ?IntegrationConnectorInterface
    {
        foreach ($this->connectors as $connector) {
            if ($connector->supports($type)) {
                return $connector;
            }
        }

        return null;
    }

    /**
     * @return array<string, IntegrationConnectorInterface>
     */
    public function getConnectors(): array
    {
        $map = [];
        foreach ($this->connectors as $connector) {
            $map[$connector->getType()] = $connector;
        }

        return $map;
    }
}
