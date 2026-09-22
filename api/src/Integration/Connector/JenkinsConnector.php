<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Entity\JenkinsIntegrationParam;
use App\Integration\Dto\ConnectionTestResult;
use App\Service\ProxyResolver;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JenkinsConnector implements IntegrationConnectorInterface, ProjectProviderConnectorInterface
{
    private readonly ProxyResolver $proxyResolver;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?ProxyResolver $proxyResolver = null,
    ) {
        $this->proxyResolver = $proxyResolver ?? new ProxyResolver();
    }

    public function supports(string $type): bool
    {
        return 'jenkins' === strtolower($type);
    }

    public function getType(): string
    {
        return 'jenkins';
    }

    public function getName(): string
    {
        return 'Jenkins';
    }

    public function testConnection(Integration $integration): ConnectionTestResult
    {
        $server = $integration->getServer();
        if (null === $server) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour Jenkins.");
        }

        $requestOptions = $this->buildRequestOptions($integration);

        try {
            $response = $this->httpClient->request('GET', $baseUrl.'/api/json', $requestOptions);
            $statusCode = $response->getStatusCode();

            if (200 === $statusCode) {
                $headers = $response->getHeaders(false);
                $version = $headers['x-jenkins'][0] ?? null;
                $data = $response->toArray(false);
                $description = $data['nodeDescription'] ?? null;

                $message = 'Connexion réussie à Jenkins';
                if (null !== $version) {
                    $message .= sprintf(' (version %s)', $version);
                }

                return ConnectionTestResult::success(
                    $message,
                    array_filter([
                        'version' => $version,
                        'description' => $description,
                        'url' => $baseUrl,
                    ])
                );
            }

            if (401 === $statusCode || 403 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Échec d'authentification Jenkins (HTTP %d) : identifiants ou jeton d'accès invalides.", $statusCode)
                );
            }

            if (404 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Instance Jenkins introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            return ConnectionTestResult::failure(
                sprintf('Le serveur Jenkins a répondu avec le statut HTTP %d.', $statusCode)
            );
        } catch (TransportExceptionInterface $e) {
            return ConnectionTestResult::failure(
                sprintf("Délai d'attente dépassé ou serveur Jenkins injoignable : %s", $e->getMessage())
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur lors du test de connexion Jenkins : %s', $e->getMessage())
            );
        }
    }

    /**
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    public function getProjects(Integration $integration): array
    {
        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            throw new \InvalidArgumentException("L'hôte du serveur Jenkins n'est pas renseigné.");
        }

        $requestOptions = $this->buildRequestOptions($integration);
        $url = $baseUrl.'/api/json?tree=jobs[name,url,_class,jobs[name,url,_class,jobs[name,url,_class,jobs[name,url,_class]]]]';

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);
            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException(sprintf('Jenkins a répondu avec le statut HTTP %d.', $response->getStatusCode()));
            }

            $data = $response->toArray(false);
            $folders = [];
            $this->extractFoldersRecursively($data['jobs'] ?? [], '', '', $folders);

            // Tri alphabétique sur le nom d'affichage
            usort($folders, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

            return $folders;
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Erreur lors de la récupération des dossiers Jenkins : %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchJobs(Integration $integration, string $folder): array
    {
        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            throw new \InvalidArgumentException("L'hôte du serveur Jenkins n'est pas renseigné.");
        }

        $normalizedFolder = JenkinsIntegrationParam::normalizeFolder($folder);
        if ('' === $normalizedFolder) {
            throw new \InvalidArgumentException('Le chemin du dossier Jenkins est invalide ou vide.');
        }

        $requestOptions = $this->buildRequestOptions($integration);
        $url = $baseUrl.'/'.$normalizedFolder.'api/json?tree=name,url,description,jobs[name,url,color,_class,description,lastBuild[number,url,result,timestamp,duration,building],lastSuccessfulBuild[number,url,timestamp],lastFailedBuild[number,url,timestamp]]';

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);
            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                throw new \RuntimeException(sprintf("Le dossier Jenkins '%s' est introuvable (HTTP 404).", $normalizedFolder));
            }

            if (401 === $statusCode || 403 === $statusCode) {
                throw new \RuntimeException(sprintf("Accès non autorisé au dossier Jenkins '%s' (HTTP %d).", $normalizedFolder, $statusCode));
            }

            if (200 !== $statusCode) {
                throw new \RuntimeException(sprintf("Jenkins a répondu avec le code HTTP %d pour le dossier '%s'.", $statusCode, $normalizedFolder));
            }

            $data = $response->toArray(false);
            $rawJobs = $data['jobs'] ?? [];
            $jobs = [];

            foreach ($rawJobs as $rawJob) {
                $jobName = (string) ($rawJob['name'] ?? '');
                if ('' === $jobName) {
                    continue;
                }

                $color = (string) ($rawJob['color'] ?? 'unknown');
                $class = (string) ($rawJob['_class'] ?? '');
                $jobUrl = (string) ($rawJob['url'] ?? ($baseUrl.'/'.$normalizedFolder.'job/'.rawurlencode($jobName).'/'));
                $description = (string) ($rawJob['description'] ?? '');

                // Statut déduit de la couleur Jenkins
                $statusInfo = $this->parseJobStatus($color, $rawJob['lastBuild'] ?? null);

                $lastBuild = null;
                if (!empty($rawJob['lastBuild'])) {
                    $lb = $rawJob['lastBuild'];
                    $durationMs = (int) ($lb['duration'] ?? 0);
                    $durationSec = (int) round($durationMs / 1000);
                    $lastBuild = [
                        'number' => (int) ($lb['number'] ?? 0),
                        'url' => (string) ($lb['url'] ?? ''),
                        'result' => (string) ($lb['result'] ?? ($statusInfo['status'] ?? 'UNKNOWN')),
                        'timestamp' => (int) ($lb['timestamp'] ?? 0),
                        'duration' => $durationMs,
                        'durationFormatted' => $durationSec > 0 ? sprintf('%dm %02ds', intdiv($durationSec, 60), $durationSec % 60) : '-',
                        'building' => (bool) ($lb['building'] ?? false),
                    ];
                }

                $lastSuccessfulBuild = null;
                if (!empty($rawJob['lastSuccessfulBuild'])) {
                    $lsb = $rawJob['lastSuccessfulBuild'];
                    $lastSuccessfulBuild = [
                        'number' => (int) ($lsb['number'] ?? 0),
                        'url' => (string) ($lsb['url'] ?? ''),
                        'timestamp' => (int) ($lsb['timestamp'] ?? 0),
                    ];
                }

                $lastFailedBuild = null;
                if (!empty($rawJob['lastFailedBuild'])) {
                    $lfb = $rawJob['lastFailedBuild'];
                    $lastFailedBuild = [
                        'number' => (int) ($lfb['number'] ?? 0),
                        'url' => (string) ($lfb['url'] ?? ''),
                        'timestamp' => (int) ($lfb['timestamp'] ?? 0),
                    ];
                }

                $jobs[] = [
                    'name' => $jobName,
                    'displayName' => str_replace('_', ' ', $jobName),
                    'url' => $jobUrl,
                    'color' => $color,
                    'class' => $class,
                    'description' => $description,
                    'status' => $statusInfo['status'],
                    'statusLabel' => $statusInfo['label'],
                    'statusBadgeColor' => $statusInfo['badgeColor'],
                    'isBuilding' => $statusInfo['isBuilding'],
                    'lastBuild' => $lastBuild,
                    'lastSuccessfulBuild' => $lastSuccessfulBuild,
                    'lastFailedBuild' => $lastFailedBuild,
                ];
            }

            return $jobs;
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf("Erreur lors de la récupération des jobs du dossier '%s' : %s", $normalizedFolder, $e->getMessage()), 0, $e);
        }
    }

    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult
    {
        $connectionResult = $this->testConnection($integration);
        if (!$connectionResult->success) {
            return $connectionResult;
        }

        if (null === $param) {
            return $connectionResult;
        }

        if (!$param instanceof JenkinsIntegrationParam) {
            $param = JenkinsIntegrationParam::fromArray($param->toArray());
        }

        $folder = $param->getFolder();
        $jobName = $param->getJobName();

        // Mode prioritaire : Dossier de jobs
        if (null !== $folder && '' !== $folder) {
            try {
                $jobs = $this->fetchJobs($integration, $folder);
                $count = count($jobs);

                return ConnectionTestResult::success(
                    sprintf("Dossier Jenkins '%s' accessible : %d job(s) référencé(s).", $folder, $count),
                    [
                        'folder' => $folder,
                        'jobsCount' => $count,
                        'jobs' => array_map(static fn (array $j): array => [
                            'name' => $j['name'],
                            'status' => $j['status'],
                            'url' => $j['url'],
                        ], $jobs),
                    ]
                );
            } catch (\Throwable $e) {
                return ConnectionTestResult::failure(
                    sprintf("Échec de vérification du dossier Jenkins '%s' : %s", $folder, $e->getMessage()),
                    ['folder' => $folder]
                );
            }
        }

        // Mode repli : Job unitaire
        if (null === $jobName || '' === $jobName) {
            return ConnectionTestResult::failure("Le dossier ou le nom du job Jenkins n'est pas renseigné dans les paramètres de la liaison.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        $requestOptions = $this->buildRequestOptions($integration);

        try {
            $url = $baseUrl.'/job/'.urlencode($jobName).'/api/json';
            $response = $this->httpClient->request('GET', $url, $requestOptions);
            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Le job Jenkins '%s' est introuvable sur le serveur (HTTP 404).", $jobName),
                    ['jobName' => $jobName, 'url' => $baseUrl]
                );
            }

            if (401 === $statusCode || 403 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Accès non autorisé au job Jenkins '%s' (HTTP %d).", $jobName, $statusCode),
                    ['jobName' => $jobName]
                );
            }

            if (200 !== $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Le serveur Jenkins a répondu avec le statut HTTP %d pour le job '%s'.", $statusCode, $jobName)
                );
            }

            $data = $response->toArray(false);
            $displayName = $data['displayName'] ?? $data['name'] ?? $jobName;
            $color = $data['color'] ?? 'unknown';

            return ConnectionTestResult::success(
                sprintf("Job Jenkins '%s' accessible et opérationnel.", $displayName),
                [
                    'jobName' => $jobName,
                    'displayName' => $displayName,
                    'color' => $color,
                    'url' => $data['url'] ?? ($baseUrl.'/job/'.urlencode($jobName)),
                ]
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf("Erreur lors de la vérification du job Jenkins '%s' : %s", $jobName, $e->getMessage())
            );
        }
    }

    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array
    {
        if (!$param instanceof JenkinsIntegrationParam) {
            $param = JenkinsIntegrationParam::fromArray($param?->toArray() ?? []);
        }

        $folder = $param->getFolder();
        $baseUrl = $this->resolveBaseUrl($integration);

        if (null !== $folder && '' !== $folder) {
            $jobs = $this->fetchJobs($integration, $folder);
            $folderUrl = $baseUrl.'/'.$folder;

            $total = count($jobs);
            $success = 0;
            $failure = 0;
            $unstable = 0;
            $building = 0;
            $disabled = 0;
            $lastBuild = null;
            $latestTimestamp = 0;

            foreach ($jobs as $j) {
                if ($j['isBuilding']) {
                    ++$building;
                }
                match ($j['status']) {
                    'SUCCESS' => $success++,
                    'FAILURE' => $failure++,
                    'UNSTABLE' => $unstable++,
                    'DISABLED' => $disabled++,
                    default => null,
                };

                if (!empty($j['lastBuild']['timestamp']) && $j['lastBuild']['timestamp'] > $latestTimestamp) {
                    $latestTimestamp = $j['lastBuild']['timestamp'];
                    $lastBuild = array_merge($j['lastBuild'], [
                        'jobName' => $j['name'],
                        'jobUrl' => $j['url'],
                    ]);
                }
            }

            return [
                'type' => 'jenkins',
                'connected' => true,
                'folder' => $folder,
                'folderName' => basename(rtrim($folder, '/')),
                'url' => $folderUrl,
                'jobs' => $jobs,
                'stats' => [
                    'total' => $total,
                    'success' => $success,
                    'failure' => $failure,
                    'unstable' => $unstable,
                    'building' => $building,
                    'disabled' => $disabled,
                ],
                'lastBuild' => $lastBuild,
            ];
        }

        // Si aucun dossier configuré mais job unitaire
        $jobName = $param->getJobName();
        if (null !== $jobName && '' !== $jobName) {
            $requestOptions = $this->buildRequestOptions($integration);
            $url = $baseUrl.'/job/'.urlencode($jobName).'/api/json';
            $res = $this->httpClient->request('GET', $url, $requestOptions);
            $data = $res->toArray(false);

            return [
                'type' => 'jenkins',
                'connected' => true,
                'jobName' => $jobName,
                'url' => $data['url'] ?? ($baseUrl.'/job/'.urlencode($jobName)),
                'color' => $data['color'] ?? 'unknown',
                'jobs' => [],
                'stats' => [
                    'total' => 1,
                    'success' => ($data['color'] ?? '') === 'blue' ? 1 : 0,
                    'failure' => ($data['color'] ?? '') === 'red' ? 1 : 0,
                    'unstable' => 0,
                    'building' => str_ends_with($data['color'] ?? '', '_anime') ? 1 : 0,
                    'disabled' => 0,
                ],
            ];
        }

        return [
            'type' => 'jenkins',
            'connected' => false,
            'message' => 'Aucun dossier ou job Jenkins configuré.',
            'jobs' => [],
            'stats' => ['total' => 0, 'success' => 0, 'failure' => 0, 'unstable' => 0, 'building' => 0, 'disabled' => 0],
        ];
    }

    /**
     * @param array<int, array<string, mixed>>                              $rawJobs
     * @param array<int, array{id: string, name: string, raw_name: string}> $folders
     */
    private function extractFoldersRecursively(array $rawJobs, string $parentPath, string $parentDisplayName, array &$folders): void
    {
        foreach ($rawJobs as $job) {
            $name = (string) ($job['name'] ?? '');
            if ('' === $name) {
                continue;
            }

            $class = (string) ($job['_class'] ?? '');
            $isFolder = str_contains($class, 'Folder') || !empty($job['jobs']);

            if ($isFolder) {
                $currentPath = $parentPath.'job/'.$name.'/';
                $currentDisplayName = '' !== $parentDisplayName ? $parentDisplayName.' » '.$name : $name;

                $folders[] = [
                    'id' => $currentPath,
                    'name' => $currentDisplayName,
                    'raw_name' => $name,
                ];

                if (!empty($job['jobs']) && is_array($job['jobs'])) {
                    $this->extractFoldersRecursively($job['jobs'], $currentPath, $currentDisplayName, $folders);
                }
            }
        }
    }

    /**
     * @param array<string, mixed>|null $lastBuild
     *
     * @return array{status: string, label: string, badgeColor: "success"|"error"|"warning"|"primary"|"neutral", isBuilding: bool}
     */
    private function parseJobStatus(string $color, ?array $lastBuild = null): array
    {
        $isBuilding = str_ends_with($color, '_anime') || !empty($lastBuild['building']);
        $baseColor = str_replace('_anime', '', $color);

        if ($isBuilding) {
            return [
                'status' => 'BUILDING',
                'label' => 'En cours...',
                'badgeColor' => 'primary',
                'isBuilding' => true,
            ];
        }

        return match ($baseColor) {
            'blue' => [
                'status' => 'SUCCESS',
                'label' => 'Succès',
                'badgeColor' => 'success',
                'isBuilding' => false,
            ],
            'red' => [
                'status' => 'FAILURE',
                'label' => 'Échec',
                'badgeColor' => 'error',
                'isBuilding' => false,
            ],
            'yellow' => [
                'status' => 'UNSTABLE',
                'label' => 'Instable',
                'badgeColor' => 'warning',
                'isBuilding' => false,
            ],
            'disabled', 'grey' => [
                'status' => 'DISABLED',
                'label' => 'Désactivé',
                'badgeColor' => 'neutral',
                'isBuilding' => false,
            ],
            'aborted' => [
                'status' => 'ABORTED',
                'label' => 'Annulé',
                'badgeColor' => 'neutral',
                'isBuilding' => false,
            ],
            'notbuilt' => [
                'status' => 'NOT_BUILT',
                'label' => 'Non compilé',
                'badgeColor' => 'neutral',
                'isBuilding' => false,
            ],
            default => [
                'status' => 'UNKNOWN',
                'label' => 'Inconnu',
                'badgeColor' => 'neutral',
                'isBuilding' => false,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestOptions(Integration $integration): array
    {
        $server = $integration->getServer();
        $username = $server?->getUsername();
        $token = $server?->getPassword();
        $authTypeName = $server?->getAuthenticationType()?->getName();
        $options = $server?->getOptions() ?? [];
        $host = (string) ($server?->getHost() ?? '');

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $requestOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $requestOptions['proxy'] = $proxyOptions['proxy'];
        }

        if (!empty($username) && !empty($token)) {
            $requestOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token)) {
            if ('Token' === $authTypeName) {
                $requestOptions['headers']['Authorization'] = 'Bearer '.trim((string) $token);
            } else {
                $requestOptions['auth_basic'] = [(string) $username, (string) $token];
            }
        }

        return $requestOptions;
    }

    private function resolveBaseUrl(Integration $integration): ?string
    {
        $server = $integration->getServer();
        if (null === $server) {
            return null;
        }

        $host = $server->getHost();
        if (null === $host || '' === $host) {
            return null;
        }

        $options = $server->getOptions();
        $scheme = $options['protocol'] ?? 'http';
        $port = $server->getPort();

        $url = sprintf('%s://%s', $scheme, $host);
        if (null !== $port && !(('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port))) {
            $url .= ':'.$port;
        }

        if (!empty($options['path'])) {
            $url .= '/'.ltrim((string) $options['path'], '/');
        }

        return rtrim($url, '/');
    }
}
