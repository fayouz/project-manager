<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Integration\Dto\ConnectionTestResult;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MantisConnector implements IntegrationConnectorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function supports(string $type): bool
    {
        return strtolower($type) === 'mantis';
    }

    public function getType(): string
    {
        return 'mantis';
    }

    public function getName(): string
    {
        return 'Mantis Bug Tracker';
    }

    public function testConnection(Integration $integration): ConnectionTestResult
    {
        $server = $integration->getServer();
        if ($server === null) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if ($baseUrl === null || $baseUrl === '') {
            return ConnectionTestResult::failure("L'hôte du serveur n'est pas renseigné pour Mantis.");
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
            // Case 1: Pure API Token authentication (via REST API)
            if ($authTypeName === 'Token' || (!empty($token) && empty($username))) {
                return $this->testRestTokenConnection($baseUrl, (string) $token, $baseRequestOptions);
            }

            // Case 2: Username & Password / Token provided (via SOAP MantisConnect, with REST fallback)
            if (!empty($username) && !empty($token)) {
                return $this->testSoapCredentialsConnection($baseUrl, (string) $username, (string) $token, $baseRequestOptions);
            }

            // Case 3: No credentials configured - verify instance accessibility & version
            return $this->testAnonymousConnection($baseUrl, $baseRequestOptions);
        } catch (TransportExceptionInterface $e) {
            return ConnectionTestResult::failure(
                sprintf("Délai d'attente dépassé ou serveur Mantis injoignable : %s", $e->getMessage())
            );
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur lors du test de connexion Mantis : %s', $e->getMessage())
            );
        }
    }

    /**
     * @param array<string, mixed> $baseOptions
     */
    private function testRestTokenConnection(string $baseUrl, string $token, array $baseOptions): ConnectionTestResult
    {
        $options = $baseOptions;
        $cleanToken = trim($token);
        $options['headers']['Authorization'] = $cleanToken;

        $response = $this->httpClient->request('GET', $baseUrl . '/api/rest/users/me', $options);
        $statusCode = $response->getStatusCode();

        // If bare token was rejected, try with Bearer prefix
        if ($statusCode === 401 && !str_starts_with($cleanToken, 'Bearer ')) {
            $options['headers']['Authorization'] = 'Bearer ' . $cleanToken;
            $response = $this->httpClient->request('GET', $baseUrl . '/api/rest/users/me', $options);
            $statusCode = $response->getStatusCode();
        }

        if ($statusCode === 200) {
            $headers = $response->getHeaders(false);
            $version = $headers['x-mantis-version'][0] ?? null;
            $data = $response->toArray(false);
            $user = $data['users'][0] ?? $data;
            $authenticatedUsername = $user['name'] ?? $user['username'] ?? null;

            $message = 'Connexion réussie à Mantis';
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

        if ($statusCode === 401 || $statusCode === 403) {
            return ConnectionTestResult::failure(
                sprintf("Échec d'authentification Mantis (HTTP %d) : jeton d'accès invalide.", $statusCode)
            );
        }

        if ($statusCode === 404) {
            return ConnectionTestResult::failure(
                sprintf("Instance Mantis introuvable à l'adresse %s (HTTP 404).", $baseUrl)
            );
        }

        return ConnectionTestResult::failure(
            sprintf('Le serveur Mantis a répondu avec le statut HTTP %d.', $statusCode)
        );
    }

    /**
     * @param array<string, mixed> $baseOptions
     */
    private function testSoapCredentialsConnection(string $baseUrl, string $username, string $password, array $baseOptions): ConnectionTestResult
    {
        $xmlUsername = htmlspecialchars($username, ENT_XML1, 'UTF-8');
        $xmlPassword = htmlspecialchars($password, ENT_XML1, 'UTF-8');

        $soapBody = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://futureware.biz/mantisconnect">
  <soapenv:Body>
    <m:mc_projects_get_user_accessible soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
      <username xsi:type="xsd:string">{$xmlUsername}</username>
      <password xsi:type="xsd:string">{$xmlPassword}</password>
    </m:mc_projects_get_user_accessible>
  </soapenv:Body>
</soapenv:Envelope>
XML;

        $soapOptions = $baseOptions;
        $soapOptions['headers']['Content-Type'] = 'text/xml; charset=utf-8';
        $soapOptions['body'] = $soapBody;

        $response = $this->httpClient->request('POST', $baseUrl . '/api/soap/mantisconnect.php', $soapOptions);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            // SOAP not found, try REST token as fallback in case password is an API token
            return $this->testRestTokenConnection($baseUrl, $password, $baseOptions);
        }

        if ($statusCode === 200 || $statusCode === 500) {
            $content = $response->getContent(false);

            // Check for SOAP Fault (e.g. invalid credentials)
            if (str_contains($content, '<SOAP-ENV:Fault>') || str_contains($content, '<faultcode>')) {
                if (preg_match('#<faultstring>(.*?)</faultstring>#s', $content, $faultMatches)) {
                    $faultString = trim($faultMatches[1]);
                    if (stripos($faultString, 'Invalid credentials') !== false || stripos($faultString, 'Access denied') !== false) {
                        return ConnectionTestResult::failure("Échec d'authentification Mantis : identifiants invalides.");
                    }

                    return ConnectionTestResult::failure(sprintf('Erreur Mantis : %s', $faultString));
                }

                return ConnectionTestResult::failure("Échec d'authentification Mantis : identifiants invalides.");
            }

            if (str_contains($content, 'mc_projects_get_user_accessibleResponse')) {
                $headers = $response->getHeaders(false);
                $version = $headers['x-mantis-version'][0] ?? null;

                // Extract project count from SOAP array definition or items
                $projectCount = null;
                if (preg_match('#ns1:ProjectData\[(\d+)\]#', $content, $countMatches)) {
                    $projectCount = (int) $countMatches[1];
                } elseif (preg_match_all('#<item\s+xsi:type="ns1:ProjectData"#', $content, $itemMatches)) {
                    $projectCount = count($itemMatches[0]);
                }

                // If version not in header, try mc_version
                if ($version === null) {
                    $version = $this->fetchSoapVersion($baseUrl, $baseOptions);
                }

                $message = 'Connexion réussie à Mantis';
                if ($version !== null) {
                    $message .= sprintf(' (version %s)', $version);
                }
                $message .= sprintf(' pour le compte %s', $username);
                if ($projectCount !== null) {
                    $message .= sprintf(' (%d projet%s accessible%s)', $projectCount, $projectCount > 1 ? 's' : '', $projectCount > 1 ? 's' : '');
                }

                return ConnectionTestResult::success(
                    $message,
                    array_filter([
                        'version' => $version,
                        'username' => $username,
                        'projects_count' => $projectCount,
                        'url' => $baseUrl,
                    ])
                );
            }
        }

        return ConnectionTestResult::failure(
            sprintf('Le serveur Mantis a répondu avec le statut HTTP %d.', $statusCode)
        );
    }

    /**
     * @param array<string, mixed> $baseOptions
     */
    private function testAnonymousConnection(string $baseUrl, array $baseOptions): ConnectionTestResult
    {
        // 1. Try SOAP mc_version
        $version = $this->fetchSoapVersion($baseUrl, $baseOptions);

        // 2. If SOAP fails, test REST endpoint
        if ($version === null) {
            $restResponse = $this->httpClient->request('GET', $baseUrl . '/api/rest/users/me', $baseOptions);
            $statusCode = $restResponse->getStatusCode();

            if ($statusCode === 404) {
                return ConnectionTestResult::failure(
                    sprintf("Instance Mantis introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            $headers = $restResponse->getHeaders(false);
            $version = $headers['x-mantis-version'][0] ?? null;
        }

        $message = 'Instance Mantis accessible';
        if ($version !== null) {
            $message .= sprintf(' (version %s)', $version);
        }
        $message .= ' (aucun identifiant configuré)';

        return ConnectionTestResult::success(
            $message,
            array_filter([
                'version' => $version,
                'url' => $baseUrl,
            ])
        );
    }

    /**
     * @param array<string, mixed> $baseOptions
     */
    private function fetchSoapVersion(string $baseUrl, array $baseOptions): ?string
    {
        try {
            $soapBody = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://futureware.biz/mantisconnect">
  <soapenv:Body>
    <m:mc_version soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
  </soapenv:Body>
</soapenv:Envelope>
XML;
            $soapOptions = $baseOptions;
            $soapOptions['headers']['Content-Type'] = 'text/xml; charset=utf-8';
            $soapOptions['body'] = $soapBody;

            $response = $this->httpClient->request('POST', $baseUrl . '/api/soap/mantisconnect.php', $soapOptions);
            if ($response->getStatusCode() === 200) {
                $headers = $response->getHeaders(false);
                if (isset($headers['x-mantis-version'][0])) {
                    return $headers['x-mantis-version'][0];
                }

                $content = $response->getContent(false);
                if (preg_match('#<return[^>]*>([^<]+)</return>#', $content, $matches)) {
                    return trim($matches[1]);
                }
            }
        } catch (\Throwable) {
            // Squelch errors during version check
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

        // Clean scheme if present in host
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
