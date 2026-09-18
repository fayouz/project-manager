<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Integration\Dto\ConnectionTestResult;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JenkinsConnector implements IntegrationConnectorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function supports(string $type): bool
    {
        return strtolower($type) === 'jenkins';
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
        if ($server === null) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if ($baseUrl === null || $baseUrl === '') {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour Jenkins.");
        }

        $username = $server->getUsername();
        $token = $server->getPassword();
        $authTypeName = $server->getAuthenticationType()?->getName();
        $options = $server->getOptions();

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $requestOptions = [
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
                $requestOptions['proxy'] = '';
            } else {
                $requestOptions['proxy'] = $proxyVal;
            }
        }

        if (!empty($username) && !empty($token)) {
            $requestOptions['auth_basic'] = [(string) $username, (string) $token];
        } elseif (!empty($token)) {
            if ($authTypeName === 'Token') {
                $requestOptions['headers']['Authorization'] = 'Bearer ' . trim((string) $token);
            } else {
                $requestOptions['auth_basic'] = [(string) $username, (string) $token];
            }
        }

        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/api/json', $requestOptions);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $headers = $response->getHeaders(false);
                $version = $headers['x-jenkins'][0] ?? null;
                $data = $response->toArray(false);
                $description = $data['nodeDescription'] ?? null;

                $message = 'Connexion réussie à Jenkins';
                if ($version !== null) {
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

            if ($statusCode === 401 || $statusCode === 403) {
                return ConnectionTestResult::failure(
                    sprintf("Échec d'authentification Jenkins (HTTP %d) : identifiants ou jeton d'accès invalides.", $statusCode)
                );
            }

            if ($statusCode === 404) {
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
