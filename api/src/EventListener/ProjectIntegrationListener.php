<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\JenkinsIntegrationParam;
use App\Entity\ProjectIntegration;
use App\Integration\Connector\JenkinsConnector;
use App\Integration\IntegrationRegistry;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: ProjectIntegration::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: ProjectIntegration::class)]
class ProjectIntegrationListener
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
    ) {
    }

    public function prePersist(ProjectIntegration $projectIntegration, PrePersistEventArgs $event): void
    {
        $this->syncJenkinsJobs($projectIntegration);
    }

    public function preUpdate(ProjectIntegration $projectIntegration, PreUpdateEventArgs $event): void
    {
        $this->syncJenkinsJobs($projectIntegration);
    }

    private function syncJenkinsJobs(ProjectIntegration $projectIntegration): void
    {
        $integration = $projectIntegration->getIntegration();
        if ($integration === null || strtolower((string) $integration->getType()) !== 'jenkins') {
            return;
        }

        $parameters = $projectIntegration->getParameters();
        $folder = $parameters['folder'] ?? $parameters['folder_path'] ?? $parameters['job_folder'] ?? null;
        if ($folder === null || trim((string) $folder) === '') {
            return;
        }

        $normalizedFolder = JenkinsIntegrationParam::normalizeFolder((string) $folder);
        $parameters['folder'] = $normalizedFolder;

        // Si les jobs ne sont pas encore renseignés, on va les chercher dans Jenkins
        if (empty($parameters['jobs'])) {
            try {
                $connector = $this->registry->getConnector('jenkins');
                if ($connector instanceof JenkinsConnector) {
                    $jobs = $connector->fetchJobs($integration, $normalizedFolder);
                    $parameters['jobs'] = $jobs;
                    $parameters['jobs_count'] = count($jobs);
                }
            } catch (\Throwable) {
                // On tolère l'échec pour ne pas bloquer la sauvegarde si Jenkins est temporairement inaccessible
            }
        } else {
            $parameters['jobs_count'] = count($parameters['jobs']);
        }

        $projectIntegration->setParameters($parameters);
    }
}
