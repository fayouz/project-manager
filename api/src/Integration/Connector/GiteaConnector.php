<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
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
