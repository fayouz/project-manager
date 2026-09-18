<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Entity\SonarQubeIntegrationParam;
use App\Integration\Dto\ConnectionTestResult;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SonarQubeConnector implements IntegrationConnectorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function supports(string $type): bool
    {
        return strtolower($type) === 'sonarqube';
    }

    public function getType(): string
    {
        return 'sonarqube';
    }

    public function getName(): string
    {
        return 'SonarQube';
    }

    public function testConnection(Integration $integration): ConnectionTestResult
    {
        $server = $integration->getServer();
        if ($server === null) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if ($baseUrl === null || $baseUrl === '') {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour SonarQube.");
        }

        $token = $server->getPassword();
        $username = $server->getUsername();
        $authTypeName = $server->getAuthenticationType()?->getName();
        $options = $server->getOptions();

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $baseRequestOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $proxy = $options['proxy'] ?? $_SERVER['HTTP_PROXY'] ?? $_SERVER['http_proxy'] ?? $_ENV['HTTP_PROXY'] ?? $_ENV['http_proxy'] ?? (getenv('HTTP_PROXY') ?: (getenv('http_proxy') ?: null));
        if (!empty($proxy)) {
            $proxyVal = (string) $proxy;
            if (in_array(strtolower($proxyVal), ['none', 'direct', 'off'], true)) {
                $baseRequestOptions['proxy'] = '';
            } else {
                $baseRequestOptions['proxy'] = $proxyVal;
            }
        }

        try {
            // 1. Check system status & version
            $statusResponse = $this->httpClient->request('GET', $baseUrl . '/api/system/status', $baseRequestOptions);
            $statusCode = $statusResponse->getStatusCode();

            if ($statusCode === 404) {
                return ConnectionTestResult::failure(
                    sprintf("Instance SonarQube introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            if ($statusCode !== 200) {
                return ConnectionTestResult::failure(
                    sprintf('Le serveur SonarQube a répondu avec le statut HTTP %d.', $statusCode)
                );
            }

            $statusData = $statusResponse->toArray(false);
            $systemStatus = $statusData['status'] ?? null;
            $version = $statusData['version'] ?? null;

            if ($systemStatus !== null && $systemStatus !== 'UP') {
                return ConnectionTestResult::failure(
                    sprintf("Le serveur SonarQube n'est pas opérationnel (état: %s).", $systemStatus)
                );
            }

            // 2. Validate authentication if credentials or token provided
            $hasToken = $authTypeName === 'Token' || (!empty($token) && empty($username));
            $hasBasic = !empty($username) && !empty($token);

            if ($hasToken || $hasBasic) {
                $authOptions = $baseRequestOptions;

                if ($hasBasic && $authTypeName !== 'Token') {
                    $authOptions['auth_basic'] = [(string) $username, (string) $token];
                } else {
                    $cleanToken = trim((string) $token);
                    $authOptions['headers']['Authorization'] = 'Bearer ' . $cleanToken;
                }

                $userResponse = $this->httpClient->request('GET', $baseUrl . '/api/users/current', $authOptions);
                $userStatusCode = $userResponse->getStatusCode();

                // If Bearer failed with 401, fallback to token in basic auth (token:)
                if ($userStatusCode === 401 && $hasToken) {
                    $fallbackOptions = $baseRequestOptions;
                    $fallbackOptions['auth_basic'] = [trim((string) $token), ''];
                    $userResponse = $this->httpClient->request('GET', $baseUrl . '/api/users/current', $fallbackOptions);
                    $userStatusCode = $userResponse->getStatusCode();
                    if ($userStatusCode === 200) {
                        $authOptions = $fallbackOptions;
                    }
                }

                if ($userStatusCode === 401 || $userStatusCode === 403) {
                    return ConnectionTestResult::failure(
                        sprintf("Échec d'authentification SonarQube (HTTP %d) : identifiants ou jeton d'accès invalides.", $userStatusCode)
                    );
                }

                if ($userStatusCode !== 200) {
                    return ConnectionTestResult::failure(
                        sprintf('Le serveur SonarQube a répondu avec le statut HTTP %d lors de la vérification du compte.', $userStatusCode)
                    );
                }

                $userData = $userResponse->toArray(false);
                $isLoggedIn = $userData['isLoggedIn'] ?? false;

                if (!$isLoggedIn) {
                    return ConnectionTestResult::failure(
                        "Échec d'authentification SonarQube : session non connectée (identifiants invalides)."
                    );
                }

                $authenticatedUsername = $userData['name'] ?? $userData['login'] ?? $username;

                // 3. Optional: retrieve projects count
                $projectCount = $this->fetchProjectsCount($baseUrl, $authOptions);

                $message = 'Connexion réussie à SonarQube';
                if ($version !== null) {
                    $message .= sprintf(' (version %s)', $version);
                }
                if ($authenticatedUsername !== null && $authenticatedUsername !== '') {
                    $message .= sprintf(' pour le compte %s', $authenticatedUsername);
                }
                if ($projectCount !== null) {
                    $message .= sprintf(' (%d projet%s accessible%s)', $projectCount, $projectCount > 1 ? 's' : '', $projectCount > 1 ? 's' : '');
                }

                return ConnectionTestResult::success(
                    $message,
                    array_filter([
                        'version' => $version,
                        'status' => $systemStatus,
                        'username' => $authenticatedUsername,
                        'projects_count' => $projectCount,
                        'url' => $baseUrl,
                    ])
                );
            }

            // 4. Anonymous access
            $message = 'Instance SonarQube accessible';
            if ($version !== null) {
                $message .= sprintf(' (version %s)', $version);
            }
            $message .= ' (aucun identifiant configuré)';

            return ConnectionTestResult::success(
                $message,
                array_filter([
                    'version' => $version,
                    'status' => $systemStatus,
                    'url' => $baseUrl,
                ])
            );
        } catch (TransportExceptionInterface $e) {
            return ConnectionTestResult::failure(
                sprintf("Délai d'attente dépassé ou serveur SonarQube injoignable : %s", $e->getMessage())
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur lors du test de connexion SonarQube : %s', $e->getMessage())
            );
        }
    }

    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult
    {
        $connectionResult = $this->testConnection($integration);
        if (!$connectionResult->success) {
            return $connectionResult;
        }

        if ($param === null) {
            return $connectionResult;
        }

        if (!$param instanceof SonarQubeIntegrationParam) {
            $param = SonarQubeIntegrationParam::fromArray($param->toArray());
        }

        $projectKey = $param->getProjectKey();
        if ($projectKey === null || $projectKey === '') {
            return ConnectionTestResult::failure("La clé de projet SonarQube n'est pas renseignée dans les paramètres de la liaison.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        $server = $integration->getServer();
        $token = $server?->getPassword();
        $username = $server?->getUsername();
        $authTypeName = $server?->getAuthenticationType()?->getName();
        $options = $server?->getOptions() ?? [];

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $reqOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        if ($authTypeName === 'Basic' && !empty($username) && !empty($token)) {
            $reqOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token)) {
            $reqOptions['headers']['Authorization'] = 'Bearer ' . trim((string) $token);
        }

        try {
            $url = $baseUrl . '/api/components/show?component=' . urlencode($projectKey);
            $response = $this->httpClient->request('GET', $url, $reqOptions);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 404) {
                return ConnectionTestResult::failure(
                    sprintf("Le projet SonarQube '%s' est introuvable sur le serveur (HTTP 404).", $projectKey),
                    ['projectKey' => $projectKey, 'url' => $baseUrl]
                );
            }

            if ($statusCode === 401 || $statusCode === 403) {
                return ConnectionTestResult::failure(
                    sprintf("Accès non autorisé au projet SonarQube '%s' (HTTP %d).", $projectKey, $statusCode),
                    ['projectKey' => $projectKey]
                );
            }

            if ($statusCode !== 200) {
                return ConnectionTestResult::failure(
                    sprintf("Le serveur SonarQube a répondu avec le statut HTTP %d pour le projet '%s'.", $statusCode, $projectKey)
                );
            }

            $data = $response->toArray(false);
            $component = $data['component'] ?? [];
            $name = $component['name'] ?? $projectKey;

            return ConnectionTestResult::success(
                sprintf("Projet SonarQube '%s' accessible et opérationnel.", $name),
                [
                    'projectKey' => $projectKey,
                    'name' => $name,
                    'qualifier' => $component['qualifier'] ?? 'TRK',
                    'url' => $baseUrl . '/dashboard?id=' . urlencode($projectKey),
                ]
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf("Erreur lors de la vérification du projet SonarQube '%s' : %s", $projectKey, $e->getMessage())
            );
        }
    }

    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array
    {
        if (!$param instanceof SonarQubeIntegrationParam) {
            $param = SonarQubeIntegrationParam::fromArray($param?->toArray() ?? []);
        }

        $projectKey = $param->getProjectKey();
        if ($projectKey === null || $projectKey === '') {
            throw new \InvalidArgumentException("La clé de projet SonarQube n'est pas renseignée dans les paramètres de la liaison.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        $reqOptions = $this->buildRequestOptions($integration);

        // 1. Informations du composant
        $componentName = $projectKey;
        try {
            $compRes = $this->httpClient->request('GET', $baseUrl . '/api/components/show?component=' . urlencode($projectKey), $reqOptions);
            if ($compRes->getStatusCode() === 200) {
                $compData = $compRes->toArray(false);
                $componentName = $compData['component']['name'] ?? $projectKey;
            }
        } catch (\Throwable) {
        }

        // 2. État du Quality Gate
        $qualityGate = [
            'status' => 'UNKNOWN',
            'conditions' => [],
        ];
        try {
            $qgRes = $this->httpClient->request('GET', $baseUrl . '/api/qualitygates/project_status?projectKey=' . urlencode($projectKey), $reqOptions);
            if ($qgRes->getStatusCode() === 200) {
                $qgData = $qgRes->toArray(false);
                $ps = $qgData['projectStatus'] ?? [];
                $qualityGate['status'] = (string) ($ps['status'] ?? 'UNKNOWN');
                foreach ($ps['conditions'] ?? [] as $c) {
                    $qualityGate['conditions'][] = [
                        'metric' => (string) ($c['metricKey'] ?? ''),
                        'operator' => (string) ($c['comparator'] ?? ''),
                        'errorThreshold' => (string) ($c['errorThreshold'] ?? ''),
                        'actualValue' => (string) ($c['actualValue'] ?? ''),
                        'status' => (string) ($c['status'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 3. Métriques clés du projet
        $metricKeys = 'bugs,vulnerabilities,code_smells,coverage,duplicated_lines_density,security_hotspots,reliability_rating,security_rating,sqale_rating,sqale_index,ncloc';
        $measuresMap = [];
        try {
            $measuresRes = $this->httpClient->request('GET', $baseUrl . '/api/measures/component?component=' . urlencode($projectKey) . '&metricKeys=' . $metricKeys, $reqOptions);
            if ($measuresRes->getStatusCode() === 200) {
                $mData = $measuresRes->toArray(false);
                foreach ($mData['component']['measures'] ?? [] as $m) {
                    $measuresMap[$m['metric']] = $m['value'] ?? null;
                }
            }
        } catch (\Throwable) {
        }

        $ratingToLetter = static function (?string $val): string {
            if ($val === null) {
                return 'A';
            }
            return match ((int) round((float) $val)) {
                1 => 'A',
                2 => 'B',
                3 => 'C',
                4 => 'D',
                5 => 'E',
                default => 'A',
            };
        };

        $debtMinutes = isset($measuresMap['sqale_index']) ? (int) $measuresMap['sqale_index'] : 0;
        $debtDisplay = '';
        if ($debtMinutes > 0) {
            $hours = intdiv($debtMinutes, 60);
            $mins = $debtMinutes % 60;
            if ($hours > 0 && $mins > 0) {
                $debtDisplay = sprintf('%dh %dmin', $hours, $mins);
            } elseif ($hours > 0) {
                $debtDisplay = sprintf('%dh', $hours);
            } else {
                $debtDisplay = sprintf('%dmin', $mins);
            }
        } else {
            $debtDisplay = '0min';
        }

        $metrics = [
            'bugs' => (int) ($measuresMap['bugs'] ?? 0),
            'reliabilityRating' => $ratingToLetter($measuresMap['reliability_rating'] ?? null),
            'vulnerabilities' => (int) ($measuresMap['vulnerabilities'] ?? 0),
            'securityRating' => $ratingToLetter($measuresMap['security_rating'] ?? null),
            'securityHotspots' => (int) ($measuresMap['security_hotspots'] ?? 0),
            'codeSmells' => (int) ($measuresMap['code_smells'] ?? 0),
            'maintainabilityRating' => $ratingToLetter($measuresMap['sqale_rating'] ?? null),
            'debtMinutes' => $debtMinutes,
            'debtDisplay' => $debtDisplay,
            'coverage' => isset($measuresMap['coverage']) ? (float) $measuresMap['coverage'] : 0.0,
            'duplications' => isset($measuresMap['duplicated_lines_density']) ? (float) $measuresMap['duplicated_lines_density'] : 0.0,
            'linesOfCode' => (int) ($measuresMap['ncloc'] ?? 0),
        ];

        // 4. Anomalies et Code Smells ouverts
        $issues = [];
        try {
            $issuesRes = $this->httpClient->request('GET', $baseUrl . '/api/issues/search?componentKeys=' . urlencode($projectKey) . '&ps=20&resolved=false', $reqOptions);
            if ($issuesRes->getStatusCode() === 200) {
                $issuesData = $issuesRes->toArray(false);
                foreach ($issuesData['issues'] ?? [] as $iss) {
                    $componentRaw = (string) ($iss['component'] ?? '');
                    if (str_contains($componentRaw, ':')) {
                        $componentParts = explode(':', $componentRaw, 2);
                        $componentDisplay = $componentParts[1];
                    } else {
                        $componentDisplay = $componentRaw;
                    }

                    $issues[] = [
                        'key' => (string) ($iss['key'] ?? ''),
                        'severity' => (string) ($iss['severity'] ?? 'MAJOR'),
                        'type' => (string) ($iss['type'] ?? 'CODE_SMELL'),
                        'message' => (string) ($iss['message'] ?? ''),
                        'component' => $componentDisplay,
                        'line' => (int) ($iss['line'] ?? 0),
                        'effort' => (string) ($iss['effort'] ?? ''),
                        'rule' => (string) ($iss['rule'] ?? ''),
                        'creationDate' => (string) ($iss['creationDate'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        return [
            'projectKey' => $projectKey,
            'name' => $componentName,
            'url' => $baseUrl . '/dashboard?id=' . urlencode($projectKey),
            'qualityGate' => $qualityGate,
            'metrics' => $metrics,
            'issues' => $issues,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestOptions(Integration $integration): array
    {
        $server = $integration->getServer();
        $token = $server?->getPassword();
        $username = $server?->getUsername();
        $authTypeName = $server?->getAuthenticationType()?->getName();
        $options = $server?->getOptions() ?? [];

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $reqOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $proxy = $options['proxy'] ?? $_SERVER['HTTP_PROXY'] ?? $_SERVER['http_proxy'] ?? $_ENV['HTTP_PROXY'] ?? $_ENV['http_proxy'] ?? (getenv('HTTP_PROXY') ?: (getenv('http_proxy') ?: null));
        if (!empty($proxy)) {
            $proxyVal = (string) $proxy;
            if (in_array(strtolower($proxyVal), ['none', 'direct', 'off'], true)) {
                $reqOptions['proxy'] = '';
            } else {
                $reqOptions['proxy'] = $proxyVal;
            }
        }

        if ($authTypeName === 'Basic' && !empty($username) && !empty($token)) {
            $reqOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token)) {
            $cleanToken = trim((string) $token);
            $reqOptions['headers']['Authorization'] = 'Bearer ' . $cleanToken;
        }

        return $reqOptions;
    }

    /**
     * @param array<string, mixed> $authOptions
     */
    private function fetchProjectsCount(string $baseUrl, array $authOptions): ?int
    {
        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/components/search_projects?ps=1', $authOptions);
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray(false);
                if (isset($data['paging']['total']) && is_numeric($data['paging']['total'])) {
                    return (int) $data['paging']['total'];
                }
            }
        } catch (\Throwable) {
            // Non-critical, ignore
        }

        return null;
    }

    private function resolveBaseUrl(Integration $integration): ?string
    {
        $server = $integration->getServer();
        if ($server === null) {
            return null;
        }

        $host = $server->getHost();
        if ($host === null || $host === '') {
            return null;
        }

        $host = preg_replace('#^https?://#', '', rtrim($host, '/'));

        $options = $server->getOptions();
        $scheme = $options['protocol'] ?? 'http';
        $port = $server->getPort();

        $url = sprintf('%s://%s', $scheme, $host);
        if ($port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $url .= ':' . $port;
        }

        if (!empty($options['path'])) {
            $url .= '/' . ltrim((string) $options['path'], '/');
        }

        return rtrim($url, '/');
    }
}
