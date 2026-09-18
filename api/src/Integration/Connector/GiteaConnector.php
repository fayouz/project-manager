<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\GiteaIntegrationParam;
use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Integration\Dto\ConnectionTestResult;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GiteaConnector implements IntegrationConnectorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function supports(string $type): bool
    {
        return strtolower($type) === 'gitea';
    }

    public function getType(): string
    {
        return 'gitea';
    }

    public function getName(): string
    {
        return 'Gitea';
    }

    public function testConnection(Integration $integration): ConnectionTestResult
    {
        $server = $integration->getServer();
        if ($server === null) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if ($baseUrl === null || $baseUrl === '') {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour Gitea.");
        }

        $token = $server->getPassword();
        $username = $server->getUsername();
        $authTypeName = $server->getAuthenticationType()?->getName();
        $options = $server->getOptions();

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $timeoutOptions = [
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
                $timeoutOptions['proxy'] = '';
            } else {
                $timeoutOptions['proxy'] = $proxyVal;
            }
        }

        try {
            // 1. Check Gitea version endpoint
            $versionResponse = $this->httpClient->request('GET', $baseUrl . '/api/v1/version', $timeoutOptions);
            $statusCode = $versionResponse->getStatusCode();

            if ($statusCode === 404) {
                return ConnectionTestResult::failure(
                    sprintf("Instance Gitea introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            if ($statusCode !== 200) {
                return ConnectionTestResult::failure(
                    sprintf('Le serveur Gitea a répondu avec le statut HTTP %d.', $statusCode)
                );
            }

            $versionData = $versionResponse->toArray(false);
            $version = $versionData['version'] ?? null;

            // 2. If token or basic credentials provided, validate user credentials
            if (!empty($token) || !empty($username)) {
                $userOptions = $timeoutOptions;

                if ($authTypeName === 'Basic' && !empty($username) && !empty($token)) {
                    $userOptions['auth_basic'] = [(string) $username, (string) $token];
                } elseif (!empty($token)) {
                    $userOptions['headers']['Authorization'] = 'token ' . trim((string) $token);
                }

                $userResponse = $this->httpClient->request('GET', $baseUrl . '/api/v1/user', $userOptions);
                $userStatusCode = $userResponse->getStatusCode();

                if ($userStatusCode === 401 || $userStatusCode === 403) {
                    return ConnectionTestResult::failure(
                        sprintf("Échec d'authentification Gitea (HTTP %d) : identifiants ou jeton d'accès invalides.", $userStatusCode)
                    );
                }

                if ($userStatusCode === 200) {
                    $userData = $userResponse->toArray(false);
                    $authenticatedUsername = $userData['username'] ?? $userData['login'] ?? null;

                    $message = 'Connexion réussie à Gitea';
                    if ($version !== null) {
                        $message .= sprintf(' (version %s)', $version);
                    }
                    if ($authenticatedUsername !== null) {
                        $message .= sprintf(' pour le compte %s', $authenticatedUsername);
                    }

                    return ConnectionTestResult::success(
                        $message,
                        array_filter([
                            'version' => $version,
                            'username' => $authenticatedUsername,
                            'url' => $baseUrl,
                        ])
                    );
                }

                return ConnectionTestResult::failure(
                    sprintf("Vérification de l'utilisateur Gitea échouée (HTTP %d).", $userStatusCode)
                );
            }

            $message = 'Instance Gitea accessible';
            if ($version !== null) {
                $message .= sprintf(' (version %s)', $version);
            }
            $message .= ' (aucun jeton configuré)';

            return ConnectionTestResult::success(
                $message,
                array_filter([
                    'version' => $version,
                    'url' => $baseUrl,
                ])
            );
        } catch (TransportExceptionInterface $e) {
            return ConnectionTestResult::failure(
                sprintf("Délai d'attente dépassé ou serveur Gitea injoignable : %s", $e->getMessage())
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur lors du test de connexion Gitea : %s', $e->getMessage())
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

        if (!$param instanceof GiteaIntegrationParam) {
            $param = GiteaIntegrationParam::fromArray($param->toArray());
        }

        $repo = $param->getRepository();
        if ($repo === null || $repo === '') {
            return ConnectionTestResult::failure("Le dépôt Gitea n'est pas configuré dans les paramètres de la liaison.");
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
            $reqOptions['headers']['Authorization'] = 'token ' . trim((string) $token);
        }

        try {
            $cleanRepo = ltrim(trim($repo), '/');
            $response = $this->httpClient->request('GET', $baseUrl . '/api/v1/repos/' . $cleanRepo, $reqOptions);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 404) {
                return ConnectionTestResult::failure(
                    sprintf("Le dépôt Gitea '%s' est introuvable sur le serveur (HTTP 404).", $repo),
                    ['repository' => $repo, 'url' => $baseUrl]
                );
            }

            if ($statusCode === 401 || $statusCode === 403) {
                return ConnectionTestResult::failure(
                    sprintf("Accès non autorisé au dépôt Gitea '%s' (HTTP %d).", $repo, $statusCode),
                    ['repository' => $repo]
                );
            }

            if ($statusCode !== 200) {
                return ConnectionTestResult::failure(
                    sprintf("Le serveur Gitea a répondu avec le statut HTTP %d pour le dépôt '%s'.", $statusCode, $repo)
                );
            }

            $data = $response->toArray(false);
            $fullName = $data['full_name'] ?? $repo;
            $defaultBranch = $data['default_branch'] ?? null;
            if ($param->getBranch() === null && $defaultBranch !== null) {
                $param->setBranch($defaultBranch);
            }

            $message = sprintf("Dépôt Gitea '%s' accessible et opérationnel.", $fullName);
            if ($param->getBranch() !== null) {
                $message .= sprintf(" (branche: %s)", $param->getBranch());
            }

            return ConnectionTestResult::success($message, [
                'repository' => $fullName,
                'defaultBranch' => $defaultBranch,
                'stars' => $data['stars_count'] ?? 0,
                'openIssues' => $data['open_issues_count'] ?? 0,
                'url' => $data['html_url'] ?? ($baseUrl . '/' . $fullName),
            ]);
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf("Erreur lors de la vérification du dépôt Gitea '%s' : %s", $repo, $e->getMessage())
            );
        }
    }

    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array
    {
        if (!$param instanceof GiteaIntegrationParam) {
            $param = GiteaIntegrationParam::fromArray($param?->toArray() ?? []);
        }

        $repo = $param->getRepository();
        if ($repo === null || $repo === '') {
            throw new \InvalidArgumentException("Le dépôt Gitea n'est pas configuré dans les paramètres de la liaison.");
        }

        $cleanRepo = ltrim(trim($repo), '/');
        $baseUrl = $this->resolveBaseUrl($integration);
        $reqOptions = $this->buildRequestOptions($integration);

        $configuredBranch = $param->getBranch();

        // 1. Informations générales du dépôt
        $repoInfo = [];
        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/v1/repos/' . $cleanRepo, $reqOptions);
            if ($response->getStatusCode() === 200) {
                $repoInfo = $response->toArray(false);
            }
        } catch (\Throwable) {
            // Tolérance si indisponible
        }

        $defaultBranch = !empty($configuredBranch) ? $configuredBranch : ($repoInfo['default_branch'] ?? 'main');

        // 2. Historique des commits
        $commits = [];
        try {
            $commitsUrl = $baseUrl . '/api/v1/repos/' . $cleanRepo . '/commits?limit=15';
            if (!empty($defaultBranch)) {
                $commitsUrl .= '&sha=' . urlencode($defaultBranch);
            }
            $response = $this->httpClient->request('GET', $commitsUrl, $reqOptions);
            if ($response->getStatusCode() === 200) {
                foreach ($response->toArray(false) as $item) {
                    $c = $item['commit'] ?? [];
                    $author = $c['author'] ?? [];
                    $committer = $c['committer'] ?? [];
                    $sha = (string) ($item['sha'] ?? '');
                    $commits[] = [
                        'sha' => $sha,
                        'shortSha' => substr($sha, 0, 7),
                        'message' => (string) ($c['message'] ?? ''),
                        'author' => (string) ($author['name'] ?? $committer['name'] ?? $item['author']['login'] ?? 'Inconnu'),
                        'authorUsername' => (string) ($item['author']['login'] ?? ''),
                        'authorEmail' => (string) ($author['email'] ?? ''),
                        'date' => (string) ($author['date'] ?? $committer['date'] ?? ''),
                        'url' => (string) ($item['html_url'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 3. Branches actives
        $branches = [];
        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/v1/repos/' . $cleanRepo . '/branches', $reqOptions);
            if ($response->getStatusCode() === 200) {
                foreach ($response->toArray(false) as $b) {
                    $bName = (string) ($b['name'] ?? '');
                    $branches[] = [
                        'name' => $bName,
                        'commitSha' => substr((string) ($b['commit']['id'] ?? ''), 0, 7),
                        'isDefault' => $bName === $defaultBranch,
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 4. Demandes d'intégration (Pull Requests)
        $pullRequests = [];
        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/v1/repos/' . $cleanRepo . '/pulls?state=all&limit=10', $reqOptions);
            if ($response->getStatusCode() === 200) {
                foreach ($response->toArray(false) as $p) {
                    $pullRequests[] = [
                        'number' => (int) ($p['number'] ?? 0),
                        'title' => (string) ($p['title'] ?? ''),
                        'state' => (string) ($p['state'] ?? 'open'),
                        'author' => (string) ($p['user']['username'] ?? $p['user']['login'] ?? 'Inconnu'),
                        'createdAt' => (string) ($p['created_at'] ?? ''),
                        'merged' => (bool) ($p['has_merged'] ?? false),
                        'url' => (string) ($p['html_url'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 5. Tags / Releases
        $tags = [];
        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/v1/repos/' . $cleanRepo . '/tags?limit=15', $reqOptions);
            if ($response->getStatusCode() === 200) {
                foreach ($response->toArray(false) as $t) {
                    $tags[] = [
                        'name' => (string) ($t['name'] ?? ''),
                        'commitSha' => substr((string) ($t['commit']['sha'] ?? ''), 0, 7),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 6. Arborescence des fichiers racine
        $files = [];
        try {
            $contentsUrl = $baseUrl . '/api/v1/repos/' . $cleanRepo . '/contents';
            if (!empty($defaultBranch)) {
                $contentsUrl .= '?ref=' . urlencode($defaultBranch);
            }
            $response = $this->httpClient->request('GET', $contentsUrl, $reqOptions);
            if ($response->getStatusCode() === 200) {
                foreach ($response->toArray(false) as $f) {
                    $files[] = [
                        'name' => (string) ($f['name'] ?? ''),
                        'path' => (string) ($f['path'] ?? ''),
                        'type' => (string) ($f['type'] ?? 'file'),
                        'size' => (int) ($f['size'] ?? 0),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        // 7. Contenu du README.md
        $readme = null;
        try {
            $readmeUrl = $baseUrl . '/api/v1/repos/' . $cleanRepo . '/raw/' . urlencode($defaultBranch) . '/README.md';
            $response = $this->httpClient->request('GET', $readmeUrl, $reqOptions);
            if ($response->getStatusCode() === 200) {
                $readme = $response->getContent(false);
            }
        } catch (\Throwable) {
        }

        $cloneUrl = (string) ($repoInfo['clone_url'] ?? ($baseUrl . '/' . $cleanRepo . '.git'));
        $sshUrl = (string) ($repoInfo['ssh_url'] ?? '');

        return [
            'repository' => $repo,
            'name' => $repoInfo['name'] ?? basename($cleanRepo),
            'fullName' => $repoInfo['full_name'] ?? $cleanRepo,
            'defaultBranch' => $defaultBranch,
            'isPrivate' => (bool) ($repoInfo['private'] ?? true),
            'url' => $repoInfo['html_url'] ?? ($baseUrl . '/' . $cleanRepo),
            'cloneUrl' => $cloneUrl,
            'sshUrl' => $sshUrl,
            'stats' => [
                'stars' => (int) ($repoInfo['stars_count'] ?? 0),
                'forks' => (int) ($repoInfo['forks_count'] ?? 0),
                'openIssues' => (int) ($repoInfo['open_issues_count'] ?? 0),
                'openPulls' => (int) ($repoInfo['open_pr_counter'] ?? 0),
            ],
            'commits' => $commits,
            'branches' => $branches,
            'pullRequests' => $pullRequests,
            'tags' => $tags,
            'files' => $files,
            'readme' => $readme,
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
            $reqOptions['headers']['Authorization'] = 'token ' . trim((string) $token);
        }

        return $reqOptions;
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
