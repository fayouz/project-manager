<?php

declare(strict_types=1);

namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Entity\MantisIntegrationParam;
use App\Integration\Dto\ConnectionTestResult;
use App\Service\ProxyResolver;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MantisConnector implements IntegrationConnectorInterface, ProjectProviderConnectorInterface
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
        return 'mantis' === strtolower($type);
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
        if (null === $server) {
            return ConnectionTestResult::failure("Aucun serveur n'est associé à cette intégration.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
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

        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $baseRequestOptions['proxy'] = $proxyOptions['proxy'];
        }

        try {
            // Case 1: Pure API Token authentication (via REST API)
            if ('Token' === $authTypeName || (!empty($token) && empty($username))) {
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

    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult
    {
        // 1. Tester la connectivité de base au serveur Mantis
        $connectionResult = $this->testConnection($integration);
        if (!$connectionResult->success) {
            return $connectionResult;
        }

        // Si aucun paramètre n'est spécifié, le statut de base suffit
        if (null === $param) {
            return $connectionResult;
        }

        if (!$param instanceof MantisIntegrationParam) {
            $param = MantisIntegrationParam::fromArray($param->toArray());
        }

        $projectId = $param->getProjectId();
        if (null === $projectId || '' === $projectId) {
            return ConnectionTestResult::failure("L'identifiant du projet Mantis n'est pas renseigné dans les paramètres de la liaison.");
        }

        try {
            $projects = $this->getProjects($integration);
            $foundProject = null;
            foreach ($projects as $p) {
                if ((string) $p['id'] === (string) $projectId) {
                    $foundProject = $p;
                    break;
                }
            }

            if (null === $foundProject) {
                return ConnectionTestResult::failure(
                    sprintf("Le projet Mantis #%s est introuvable ou vous n'avez pas les droits d'accès sur ce projet.", $projectId),
                    [
                        'projectId' => $projectId,
                        'server' => $this->resolveBaseUrl($integration),
                        'availableProjectsCount' => count($projects),
                    ]
                );
            }

            $rawName = $foundProject['raw_name'] ?? $foundProject['name'];
            $param->setProjectName($rawName);

            $message = sprintf(
                "Projet Mantis '%s' (#%s) accessible et opérationnel.",
                $rawName,
                $projectId
            );

            return ConnectionTestResult::success($message, [
                'projectId' => $projectId,
                'projectName' => $rawName,
                'projectDisplayName' => $foundProject['name'],
                'url' => $this->resolveBaseUrl($integration),
                'serverStatus' => $connectionResult->message,
            ]);
        } catch (\Throwable $e) {
            return ConnectionTestResult::failure(
                sprintf('Erreur lors de la vérification du projet Mantis #%s : %s', $projectId, $e->getMessage())
            );
        }
    }

    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array
    {
        if (!$param instanceof MantisIntegrationParam) {
            $param = MantisIntegrationParam::fromArray($param?->toArray() ?? []);
        }

        $projectId = (int) $param->getProjectId();
        if ($projectId <= 0) {
            throw new \InvalidArgumentException("L'identifiant du projet Mantis n'est pas renseigné dans les paramètres de la liaison.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        $server = $integration->getServer();
        $username = (string) $server?->getUsername();
        $password = (string) $server?->getPassword();

        $xmlUsername = htmlspecialchars($username, ENT_XML1, 'UTF-8');
        $xmlPassword = htmlspecialchars($password, ENT_XML1, 'UTF-8');

        $soapBody = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://futureware.biz/mantisconnect">
  <soapenv:Body>
    <m:mc_project_get_issues soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
      <username xsi:type="xsd:string">{$xmlUsername}</username>
      <password xsi:type="xsd:string">{$xmlPassword}</password>
      <project_id xsi:type="xsd:integer">{$projectId}</project_id>
      <page_number xsi:type="xsd:integer">1</page_number>
      <per_page xsi:type="xsd:integer">50</per_page>
    </m:mc_project_get_issues>
  </soapenv:Body>
</soapenv:Envelope>
XML;

        $options = $server?->getOptions() ?? [];
        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $reqOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
            'body' => $soapBody,
        ];

        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $reqOptions['proxy'] = $proxyOptions['proxy'];
        }

        $issues = [];
        try {
            $response = $this->httpClient->request('POST', $baseUrl.'/api/soap/mantisconnect.php', $reqOptions);
            if (200 === $response->getStatusCode()) {
                $xml = $response->getContent(false);
                if (preg_match_all("#<item\s+xsi:type=\"ns1:IssueData\">(.*?)</item>#s", $xml, $matches)) {
                    foreach ($matches[1] as $itemXml) {
                        preg_match("#<id xsi:type=\"xsd:integer\">(\d+)</id>#", $itemXml, $idM);
                        preg_match('#<summary xsi:type="xsd:string">(.*?)</summary>#', $itemXml, $sumM);
                        preg_match('#<description xsi:type="xsd:string">(.*?)</description>#', $itemXml, $descM);
                        preg_match('#<category xsi:type="xsd:string">(.*?)</category>#', $itemXml, $catM);
                        preg_match('#<severity[^>]*>.*?<name[^>]*>(.*?)</name>#s', $itemXml, $sevM);
                        preg_match('#<priority[^>]*>.*?<name[^>]*>(.*?)</name>#s', $itemXml, $prioM);
                        preg_match("#<status[^>]*>.*?<id[^>]*>(\d+)</id>.*?<name[^>]*>(.*?)</name>#s", $itemXml, $statM);
                        preg_match('#<resolution[^>]*>.*?<name[^>]*>(.*?)</name>#s', $itemXml, $resM);
                        preg_match('#<reporter[^>]*>.*?<name[^>]*>(.*?)</name>#s', $itemXml, $repM);
                        preg_match('#<handler[^>]*>.*?<name[^>]*>(.*?)</name>#s', $itemXml, $handM);
                        preg_match('#<date_submitted xsi:type="xsd:dateTime">(.*?)</date_submitted>#', $itemXml, $dateSubM);
                        preg_match('#<last_updated xsi:type="xsd:dateTime">(.*?)</last_updated>#', $itemXml, $dateUpM);

                        $statusCode = isset($statM[1]) ? (int) $statM[1] : 10;
                        $statusName = html_entity_decode($statM[2] ?? 'Nouveau', ENT_QUOTES | ENT_XML1, 'UTF-8');

                        $issues[] = [
                            'id' => (int) ($idM[1] ?? 0),
                            'summary' => html_entity_decode($sumM[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'description' => html_entity_decode($descM[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'category' => html_entity_decode($catM[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'severity' => html_entity_decode($sevM[1] ?? 'mineur', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'priority' => html_entity_decode($prioM[1] ?? 'normal', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'status' => $statusName,
                            'statusCode' => $statusCode,
                            'resolution' => html_entity_decode($resM[1] ?? 'ouvert', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'reporter' => html_entity_decode($repM[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'handler' => html_entity_decode($handM[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            'dateSubmitted' => $dateSubM[1] ?? '',
                            'lastUpdated' => $dateUpM[1] ?? '',
                        ];
                    }
                }
            }
        } catch (\Throwable) {
        }

        // Calcul des statistiques
        $total = count($issues);
        $resolvedCount = 0;
        $inProgressCount = 0;
        $newCount = 0;
        $bySeverity = [];

        foreach ($issues as $iss) {
            $code = $iss['statusCode'];
            $statusLower = mb_strtolower($iss['status']);
            if ($code >= 80 || str_contains($statusLower, 'résolu') || str_contains($statusLower, 'fermé')) {
                ++$resolvedCount;
            } elseif ($code >= 50 || str_contains($statusLower, 'recette') || str_contains($statusLower, 'cours') || str_contains($statusLower, 'affecté')) {
                ++$inProgressCount;
            } else {
                ++$newCount;
            }

            $sev = $iss['severity'];
            $bySeverity[$sev] = ($bySeverity[$sev] ?? 0) + 1;
        }

        $openCount = $total - $resolvedCount;
        $resolutionRate = $total > 0 ? (int) round(($resolvedCount / $total) * 100) : 100;

        $stats = [
            'total' => $total,
            'open' => $openCount,
            'inProgress' => $inProgressCount,
            'new' => $newCount,
            'resolved' => $resolvedCount,
            'resolutionRate' => $resolutionRate,
            'bySeverity' => $bySeverity,
        ];

        return [
            'projectId' => $projectId,
            'projectName' => $param->getProjectName() ?: ('Projet #'.$projectId),
            'url' => $baseUrl.'/set_project.php?project_id='.$projectId,
            'stats' => $stats,
            'issues' => $issues,
            'roadmap' => [],
        ];
    }

    /**
     * @param array<string, mixed> $baseOptions
     */
    private function testRestTokenConnection(string $baseUrl, string $token, array $baseOptions): ConnectionTestResult
    {
        $options = $baseOptions;
        $cleanToken = trim($token);
        $options['headers']['Authorization'] = $cleanToken;

        $response = $this->httpClient->request('GET', $baseUrl.'/api/rest/users/me', $options);
        $statusCode = $response->getStatusCode();

        // If bare token was rejected, try with Bearer prefix
        if (401 === $statusCode && !str_starts_with($cleanToken, 'Bearer ')) {
            $options['headers']['Authorization'] = 'Bearer '.$cleanToken;
            $response = $this->httpClient->request('GET', $baseUrl.'/api/rest/users/me', $options);
            $statusCode = $response->getStatusCode();
        }

        if (200 === $statusCode) {
            $headers = $response->getHeaders(false);
            $version = $headers['x-mantis-version'][0] ?? null;
            $data = $response->toArray(false);
            $user = $data['users'][0] ?? $data;
            $authenticatedUsername = $user['name'] ?? $user['username'] ?? null;

            $message = 'Connexion réussie à Mantis';
            if (null !== $version) {
                $message .= sprintf(' (version %s)', $version);
            }
            if (null !== $authenticatedUsername) {
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

        if (401 === $statusCode || 403 === $statusCode) {
            return ConnectionTestResult::failure(
                sprintf("Échec d'authentification Mantis (HTTP %d) : jeton d'accès invalide.", $statusCode)
            );
        }

        if (404 === $statusCode) {
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

        $response = $this->httpClient->request('POST', $baseUrl.'/api/soap/mantisconnect.php', $soapOptions);
        $statusCode = $response->getStatusCode();

        if (404 === $statusCode) {
            // SOAP not found, try REST token as fallback in case password is an API token
            return $this->testRestTokenConnection($baseUrl, $password, $baseOptions);
        }

        if (200 === $statusCode || 500 === $statusCode) {
            $content = $response->getContent(false);

            // Check for SOAP Fault (e.g. invalid credentials)
            if (str_contains($content, '<SOAP-ENV:Fault>') || str_contains($content, '<faultcode>')) {
                if (preg_match('#<faultstring>(.*?)</faultstring>#s', $content, $faultMatches)) {
                    $faultString = trim($faultMatches[1]);
                    if (false !== stripos($faultString, 'Invalid credentials') || false !== stripos($faultString, 'Access denied')) {
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
                if (null === $version) {
                    $version = $this->fetchSoapVersion($baseUrl, $baseOptions);
                }

                $message = 'Connexion réussie à Mantis';
                if (null !== $version) {
                    $message .= sprintf(' (version %s)', $version);
                }
                $message .= sprintf(' pour le compte %s', $username);
                if (null !== $projectCount) {
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
        if (null === $version) {
            $restResponse = $this->httpClient->request('GET', $baseUrl.'/api/rest/users/me', $baseOptions);
            $statusCode = $restResponse->getStatusCode();

            if (404 === $statusCode) {
                return ConnectionTestResult::failure(
                    sprintf("Instance Mantis introuvable à l'adresse %s (HTTP 404).", $baseUrl)
                );
            }

            $headers = $restResponse->getHeaders(false);
            $version = $headers['x-mantis-version'][0] ?? null;
        }

        $message = 'Instance Mantis accessible';
        if (null !== $version) {
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

            $response = $this->httpClient->request('POST', $baseUrl.'/api/soap/mantisconnect.php', $soapOptions);
            if (200 === $response->getStatusCode()) {
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

    public function getProjects(Integration $integration): array
    {
        $server = $integration->getServer();
        if (null === $server) {
            throw new \RuntimeException("Aucun serveur n'est associé à cette intégration Mantis.");
        }

        $baseUrl = $this->resolveBaseUrl($integration);
        if (null === $baseUrl || '' === $baseUrl) {
            throw new \RuntimeException("L'hôte du serveur n'est pas renseigné pour Mantis.");
        }

        $token = $server->getPassword();
        $username = $server->getUsername();
        $authTypeName = $server->getAuthenticationType()?->getName();
        $options = $server->getOptions();

        $timeout = isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : 15.0;
        $baseRequestOptions = [
            'timeout' => $timeout,
            'max_redirects' => 3,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $baseRequestOptions['proxy'] = $proxyOptions['proxy'];
        }

        // Case 1: Pure API Token authentication (via REST API)
        if ('Token' === $authTypeName || (!empty($token) && empty($username))) {
            return $this->fetchRestProjects($baseUrl, (string) $token, $baseRequestOptions);
        }

        // Case 2: Username & Password / Token provided (via SOAP MantisConnect, with REST fallback)
        if (!empty($username) && !empty($token)) {
            return $this->fetchSoapProjects($baseUrl, (string) $username, (string) $token, $baseRequestOptions);
        }

        throw new \RuntimeException("Des identifiants (nom d'utilisateur et mot de passe ou jeton d'accès) sont requis pour récupérer les projets Mantis.");
    }

    /**
     * @param array<string, mixed> $baseOptions
     *
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    private function fetchSoapProjects(string $baseUrl, string $username, string $password, array $baseOptions): array
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
        $soapOptions['headers']['SOAPAction'] = '""';
        $soapOptions['body'] = $soapBody;

        $response = $this->httpClient->request('POST', $baseUrl.'/api/soap/mantisconnect.php', $soapOptions);
        $statusCode = $response->getStatusCode();

        if (404 === $statusCode) {
            // SOAP not found, try REST projects fallback
            return $this->fetchRestProjects($baseUrl, $password, $baseOptions);
        }

        if (200 !== $statusCode) {
            $content = $response->getContent(false);
            if (str_contains($content, 'Invalid credentials') || str_contains($content, 'Access denied')) {
                throw new \RuntimeException('Identifiants Mantis incorrects (accès refusé).');
            }
            throw new \RuntimeException(sprintf('Le serveur Mantis a répondu avec le statut HTTP %d.', $statusCode));
        }

        $content = $response->getContent(false);

        return $this->parseSoapProjectsXml($content);
    }

    /**
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    private function parseSoapProjectsXml(string $xmlContent): array
    {
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($xmlContent, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            throw new \RuntimeException("Impossible d'analyser la réponse XML de Mantis.");
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('ns1', 'http://futureware.biz/mantisconnect');

        $nodes = $xpath->query('//return/item');
        $projects = [];
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement && 'return' === $node->parentNode?->nodeName) {
                $projects = array_merge($projects, $this->extractProjectNode($node));
            }
        }

        usort($projects, fn (array $a, array $b) => strnatcasecmp($a['name'], $b['name']));

        return $projects;
    }

    /**
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    private function extractProjectNode(\DOMElement $element, string $prefix = ''): array
    {
        $id = null;
        $name = null;
        $subprojectsNode = null;

        foreach ($element->childNodes as $child) {
            if (XML_ELEMENT_NODE !== $child->nodeType) {
                continue;
            }
            if ('id' === $child->nodeName) {
                $id = trim($child->nodeValue ?? '');
            } elseif ('name' === $child->nodeName) {
                $name = trim($child->nodeValue ?? '');
            } elseif ('subprojects' === $child->nodeName) {
                $subprojectsNode = $child;
            }
        }

        if (null === $id || null === $name || '' === $id) {
            return [];
        }

        $fullName = '' !== $prefix ? $prefix.' » '.$name : $name;
        $result = [
            [
                'id' => $id,
                'name' => $fullName,
                'raw_name' => $name,
            ],
        ];

        if ($subprojectsNode instanceof \DOMElement) {
            foreach ($subprojectsNode->childNodes as $child) {
                if ($child instanceof \DOMElement && 'item' === $child->nodeName) {
                    $result = array_merge($result, $this->extractProjectNode($child, $fullName));
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $baseOptions
     *
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    private function fetchRestProjects(string $baseUrl, string $token, array $baseOptions): array
    {
        $options = $baseOptions;
        $cleanToken = trim($token);
        $options['headers']['Authorization'] = $cleanToken;

        $response = $this->httpClient->request('GET', $baseUrl.'/api/rest/projects', $options);
        $statusCode = $response->getStatusCode();

        if (401 === $statusCode && !str_starts_with($cleanToken, 'Bearer ')) {
            $options['headers']['Authorization'] = 'Bearer '.$cleanToken;
            $response = $this->httpClient->request('GET', $baseUrl.'/api/rest/projects', $options);
            $statusCode = $response->getStatusCode();
        }

        if (200 !== $statusCode) {
            throw new \RuntimeException(sprintf('Échec de la récupération des projets Mantis via REST (HTTP %d).', $statusCode));
        }

        $data = $response->toArray(false);
        $rawProjects = $data['projects'] ?? [];

        $projects = $this->extractRestProjects($rawProjects);
        usort($projects, fn (array $a, array $b) => strnatcasecmp($a['name'], $b['name']));

        return $projects;
    }

    /**
     * @param array<mixed> $projectsList
     *
     * @return array<int, array{id: string, name: string, raw_name: string}>
     */
    private function extractRestProjects(array $projectsList, string $prefix = ''): array
    {
        $result = [];
        foreach ($projectsList as $p) {
            if (!is_array($p)) {
                continue;
            }
            $id = isset($p['id']) ? (string) $p['id'] : '';
            $name = isset($p['name']) ? (string) $p['name'] : '';
            if ('' === $id || '' === $name) {
                continue;
            }

            $fullName = '' !== $prefix ? $prefix.' » '.$name : $name;
            $result[] = [
                'id' => $id,
                'name' => $fullName,
                'raw_name' => $name,
            ];

            if (!empty($p['subprojects']) && is_array($p['subprojects'])) {
                $result = array_merge($result, $this->extractRestProjects($p['subprojects'], $fullName));
            }
        }

        return $result;
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

        // Clean scheme if present in host
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
