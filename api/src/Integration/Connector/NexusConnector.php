<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Entity\NexusIntegrationParam;
use App\Integration\Dto\ConnectionTestResult;
use App\Service\ProxyResolver;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NexusConnector implements IntegrationConnectorInterface, ProjectProviderConnectorInterface
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
        return 'nexus' === strtolower($type);
    }

    public function getType(): string
    {
        return 'nexus';
    }

    public function getName(): string
    {
        return 'Nexus Repository';
    }

    public function testConnection(Integration $integration): ConnectionTestResult
    {
        $server = $integration->getServer();
        if (null === $server) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour Nexus Repository.");
        }

        $reqOptions = $this->buildRequestOptions($integration);

        try {
            // 1. Statut du serveur Nexus
            $statusResponse = $this->httpClient->request('GET', $baseUrl.'/service/rest/v1/status', $reqOptions);
            $statusCode = $statusResponse->getStatusCode();

            if (404 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Instance Nexus introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            if (200 !== $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf('Le serveur Nexus a répondu avec le statut HTTP %d.', $statusCode)
                );
            }

            $headers = $statusResponse->getHeaders(false);
            $serverHeader = $headers['server'][0] ?? '';
            $version = null;
            if (preg_match('#Nexus/([0-9a-zA-Z\.\-]+)#i', $serverHeader, $matches)) {
                $version = $matches[1];
            }

            // 2. Vérification des dépôts et de l'authentification avec cumul privés / publics
            $repositories = $this->fetchRepositories($integration, true);
            $repoCount = count($repositories);

            $versionInfo = null !== $version ? sprintf('v%s', $version) : 'version indéterminée';
            $message = sprintf(
                'Connexion à Nexus Repository (%s) réussie avec succès. Dépôt(s) accessible(s) : %d.',
                $versionInfo,
                $repoCount
            );

            return ConnectionTestResult::success($message, [
                'version' => $version,
                'repositories_count' => $repoCount,
                'server_header' => $serverHeader,
            ]);
        } catch (\RuntimeException $e) {
            return ConnectionTestResult::failure($e->getMessage());
        } catch (TransportExceptionInterface $e) {
            return ConnectionTestResult::failure(
                sprintf('Impossible de joindre le serveur Nexus (%s) : %s', $baseUrl, $e->getMessage())
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur inattendue lors de la communication avec Nexus : %s', $e->getMessage())
            );
        }
    }

    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult
    {
        $test = $this->testConnection($integration);
        if (!$test->success) {
            return $test;
        }

        if ($param instanceof NexusIntegrationParam) {
            $repository = $param->getRepository();
            if (null !== $repository && '' !== $repository) {
                $repos = $this->fetchRepositories($integration);
                $found = false;
                foreach ($repos as $repo) {
                    if (isset($repo['name']) && $repo['name'] === $repository) {
                        $found = true;
                        break;
                    }
                }

                if (!$found && [] !== $repos) {
                    return ConnectionTestResult::failure(
                        sprintf("Le dépôt Nexus '%s' est introuvable sur le serveur.", $repository)
                    );
                }
            }
        }

        return $test;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array
    {
        $baseUrl = $this->resolveBaseUrl($integration) ?? '';
        $reqOptions = $this->buildRequestOptions($integration);

        $repository = null;
        $group = null;
        $format = null;

        if ($param instanceof NexusIntegrationParam) {
            $repository = $param->getRepository();
            $group = $param->getGroup();
            $format = $param->getFormat();
        }

        // 1. Liste des dépôts existants (cumul dépôts privés et publics)
        $repositoriesList = [];
        $repoInfo = null;

        $rawRepos = $this->fetchRepositories($integration);
        foreach ($rawRepos as $r) {
            $rName = (string) ($r['name'] ?? '');
            $repositoriesList[] = [
                'name' => $rName,
                'format' => (string) ($r['format'] ?? ''),
                'type' => (string) ($r['type'] ?? ''),
                'url' => (string) ($r['url'] ?? ''),
                'online' => (bool) ($r['online'] ?? true),
            ];

            if (null !== $repository && $rName === $repository) {
                $repoInfo = $r;
            }
        }

        // 2. Si un dépôt est configuré, récupérer les composants et actifs
        $components = [];
        $assets = [];

        if (null !== $repository && '' !== $repository) {
            try {
                $compUrl = sprintf(
                    '%s/service/rest/v1/components?repository=%s',
                    $baseUrl,
                    urlencode($repository)
                );
                $compRes = $this->requestWithFallback('GET', $compUrl, $integration);
                if (null !== $compRes) {
                    $compData = $compRes->toArray(false);
                    foreach ($compData['items'] ?? [] as $item) {
                        $itemGroup = (string) ($item['group'] ?? '');
                        if (null !== $group && '' !== $group && !str_contains($itemGroup, $group)) {
                            continue;
                        }

                        $itemAssets = [];
                        foreach ($item['assets'] ?? [] as $asset) {
                            $itemAssets[] = [
                                'id' => (string) ($asset['id'] ?? ''),
                                'downloadUrl' => (string) ($asset['downloadUrl'] ?? ''),
                                'path' => (string) ($asset['path'] ?? ''),
                                'format' => (string) ($asset['format'] ?? ''),
                                'fileSize' => isset($asset['fileSize']) ? (int) $asset['fileSize'] : null,
                                'contentType' => (string) ($asset['contentType'] ?? ''),
                                'lastModified' => (string) ($asset['lastModified'] ?? ''),
                                'checksum' => $asset['checksum'] ?? [],
                            ];
                        }

                        $components[] = [
                            'id' => (string) ($item['id'] ?? ''),
                            'repository' => (string) ($item['repository'] ?? ''),
                            'format' => (string) ($item['format'] ?? ''),
                            'group' => $itemGroup,
                            'name' => (string) ($item['name'] ?? ''),
                            'version' => (string) ($item['version'] ?? ''),
                            'assets' => $itemAssets,
                        ];
                    }
                }
            } catch (\Throwable) {
                // Tolérance
            }

            try {
                $assetUrl = sprintf(
                    '%s/service/rest/v1/assets?repository=%s',
                    $baseUrl,
                    urlencode($repository)
                );
                $assetRes = $this->requestWithFallback('GET', $assetUrl, $integration);
                if (null !== $assetRes) {
                    $assetData = $assetRes->toArray(false);
                    foreach ($assetData['items'] ?? [] as $asset) {
                        $assets[] = [
                            'id' => (string) ($asset['id'] ?? ''),
                            'downloadUrl' => (string) ($asset['downloadUrl'] ?? ''),
                            'path' => (string) ($asset['path'] ?? ''),
                            'format' => (string) ($asset['format'] ?? ''),
                            'fileSize' => isset($asset['fileSize']) ? (int) $asset['fileSize'] : null,
                            'contentType' => (string) ($asset['contentType'] ?? ''),
                            'lastModified' => (string) ($asset['lastModified'] ?? ''),
                            'checksum' => $asset['checksum'] ?? [],
                        ];
                    }
                }
            } catch (\Throwable) {
                // Tolérance
            }
        }

        $webUrl = $baseUrl;
        if (null !== $repository && '' !== $repository) {
            $webUrl .= '/#browse/browse:'.urlencode($repository);
        }

        return [
            'repository' => $repository,
            'group' => $group,
            'format' => $repoInfo['format'] ?? $format,
            'type' => $repoInfo['type'] ?? null,
            'online' => $repoInfo['online'] ?? true,
            'url' => $webUrl,
            'baseUrl' => $baseUrl,
            'repositories' => $repositoriesList,
            'componentsCount' => count($components),
            'components' => $components,
            'assetsCount' => count($assets),
            'assets' => $assets,
        ];
    }

    /**
     * @return array<int, array{id: string|int, name: string, raw_name?: string, format?: string, type?: string, url?: string}>
     */
    public function getProjects(Integration $integration): array
    {
        $repos = $this->fetchRepositories($integration);
        $result = [];
        foreach ($repos as $repo) {
            $name = (string) ($repo['name'] ?? '');
            if ('' === $name) {
                continue;
            }

            $fmt = (string) ($repo['format'] ?? 'raw');
            $type = (string) ($repo['type'] ?? 'hosted');

            $result[] = [
                'id' => $name,
                'name' => sprintf('%s (%s - %s)', $name, $fmt, $type),
                'raw_name' => $name,
                'format' => $fmt,
                'type' => $type,
                'url' => (string) ($repo['url'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Récupère la liste des dépôts en cumulant les dépôts privés (avec authentification) et publics (anonyme).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchRepositories(Integration $integration, bool $throwAuthErrors = false): array
    {
        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            return [];
        }

        $server = $integration->getServer();
        $authTypeName = $server?->getAuthenticationType()?->getName();
        $isNoAuth = 'Aucune' === $authTypeName || 'None' === $authTypeName;
        $hasCredentials = !empty($server?->getUsername()) || !empty($server?->getPassword());

        /** @var array<string, array<string, mixed>> $reposByName */
        $reposByName = [];

        // 1. Récupération des dépôts privés avec authentification (si auth configurée)
        if (!$isNoAuth) {
            try {
                $authOptions = $this->buildRequestOptions($integration, false);
                $response = $this->httpClient->request('GET', $baseUrl.'/service/rest/v1/repositories', $authOptions);
                $statusCode = $response->getStatusCode();

                if ($throwAuthErrors) {
                    if (401 === $statusCode) {
                        throw new \RuntimeException('Identifiants invalides pour accéder à Nexus Repository (HTTP 401).');
                    }
                    if (403 === $statusCode) {
                        throw new \RuntimeException("Permissions insuffisantes pour l'utilisateur sur Nexus Repository (HTTP 403).");
                    }
                }

                if (200 === $statusCode) {
                    $authRepos = $response->toArray(false);
                    foreach ($authRepos as $repo) {
                        $name = (string) ($repo['name'] ?? '');
                        if ('' !== $name) {
                            $reposByName[$name] = $repo;
                        }
                    }
                }
            } catch (\RuntimeException $e) {
                if ($throwAuthErrors) {
                    throw $e;
                }
            } catch (\Throwable) {
                // Tolérance
            }
        }

        // 2. Si authentification configurée avec identifiants, ou "Aucune", récupérer les dépôts publics en anonyme pour cumuler privés et publics
        if ($isNoAuth || $hasCredentials) {
            try {
                $anonOptions = $this->buildRequestOptions($integration, true);
                $anonResponse = $this->httpClient->request('GET', $baseUrl.'/service/rest/v1/repositories', $anonOptions);
                if (200 === $anonResponse->getStatusCode()) {
                    $anonRepos = $anonResponse->toArray(false);
                    foreach ($anonRepos as $repo) {
                        $name = (string) ($repo['name'] ?? '');
                        if ('' !== $name && !isset($reposByName[$name])) {
                            $reposByName[$name] = $repo;
                        }
                    }
                }
            } catch (\Throwable) {
                // Tolérance
            }
        }

        return array_values($reposByName);
    }

    /**
     * Effectue une requête HTTP avec repli automatique en mode anonyme si la requête authentifiée échoue.
     *
     * @param array<string, mixed> $extraOptions
     */
    private function requestWithFallback(string $method, string $url, Integration $integration, array $extraOptions = []): ?\Symfony\Contracts\HttpClient\ResponseInterface
    {
        $server = $integration->getServer();
        $authTypeName = $server?->getAuthenticationType()?->getName();
        $isNoAuth = 'Aucune' === $authTypeName || 'None' === $authTypeName;
        $hasCredentials = !empty($server?->getUsername()) || !empty($server?->getPassword());

        if (!$isNoAuth && $hasCredentials) {
            try {
                $options = array_merge($this->buildRequestOptions($integration, false), $extraOptions);
                $response = $this->httpClient->request($method, $url, $options);
                $status = $response->getStatusCode();
                if ($status >= 200 && $status < 300) {
                    return $response;
                }
            } catch (\Throwable) {
                // Essayer en anonyme
            }
        }

        try {
            $anonOptions = array_merge($this->buildRequestOptions($integration, true), $extraOptions);
            $response = $this->httpClient->request($method, $url, $anonOptions);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $response;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestOptions(Integration $integration, bool $anonymous = false): array
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

        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $reqOptions['proxy'] = $proxyOptions['proxy'];
        }

        if ($anonymous || 'Aucune' === $authTypeName || 'None' === $authTypeName) {
            return $reqOptions;
        }

        if ('Basic' === $authTypeName && !empty($username) && !empty($token)) {
            $reqOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token) && !empty($username)) {
            $reqOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token)) {
            $cleanToken = trim((string) $token);
            $reqOptions['headers']['Authorization'] = 'Bearer '.$cleanToken;
        }

        return $reqOptions;
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

        $host = preg_replace('#^https?://#', '', rtrim($host, '/'));

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
