<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;

interface ProjectProviderConnectorInterface
{
    /**
     * @return array<int, array{id: string|int, name: string, raw_name?: string}>
     */
    public function getProjects(Integration $integration): array;
}
