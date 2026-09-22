<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Integration;
use App\Entity\Proxy;
use App\Entity\Server;

class ProxyResolver
{
    /**
     * Resolves proxy options for Symfony HttpClient requests.
     *
     * Chain of priority:
     * 1. Proxy explicitly selected on the integration ($integration->getProxy()).
     * 2. Proxy selected on the associated server ($server->getProxy()).
     * 3. Legacy JSON option $server->getOptions()['proxy'].
     * 4. System environment fallback (HTTP_PROXY / HTTPS_PROXY) if target host is not in NO_PROXY or internal.
     *
     * @return array{proxy?: string}
     */
    public function resolveProxyOptions(Integration $integration): array
    {
        $server = $integration->getServer();
        $targetHost = (string) ($server?->getHost() ?? '');

        // 1. Explicit proxy on Integration
        $integrationProxy = $integration->getProxy();
        if (null !== $integrationProxy) {
            return $this->resolveFromProxyEntity($integrationProxy, $targetHost);
        }

        // 2. Proxy on Server
        $serverProxy = $server?->getProxy();
        if (null !== $serverProxy) {
            return $this->resolveFromProxyEntity($serverProxy, $targetHost);
        }

        // 3. Legacy proxy in Server options
        $options = $server?->getOptions() ?? [];
        if (isset($options['proxy']) && '' !== $options['proxy'] && null !== $options['proxy']) {
            $legacyProxy = (string) $options['proxy'];
            if (in_array(strtolower(trim($legacyProxy)), ['none', 'direct', 'off'], true)) {
                return ['proxy' => ''];
            }

            if ($this->isHostExcluded($targetHost, null)) {
                return ['proxy' => ''];
            }

            return ['proxy' => $legacyProxy];
        }

        // 4. System / environment fallback
        return $this->resolveSystemFallback($targetHost);
    }

    /**
     * Alias for resolveProxyOptions.
     *
     * @return array{proxy?: string}
     */
    public function resolveRequestOptions(Integration $integration): array
    {
        return $this->resolveProxyOptions($integration);
    }

    /**
     * Resolves proxy options for a standalone Server instance.
     *
     * @return array{proxy?: string}
     */
    public function resolveProxyForServer(Server $server): array
    {
        $targetHost = (string) ($server->getHost() ?? '');
        $proxy = $server->getProxy();
        if (null !== $proxy) {
            return $this->resolveFromProxyEntity($proxy, $targetHost);
        }

        $options = $server->getOptions();
        if (isset($options['proxy']) && '' !== $options['proxy'] && null !== $options['proxy']) {
            $legacyProxy = (string) $options['proxy'];
            if (in_array(strtolower(trim($legacyProxy)), ['none', 'direct', 'off'], true)) {
                return ['proxy' => ''];
            }

            if ($this->isHostExcluded($targetHost, null)) {
                return ['proxy' => ''];
            }

            return ['proxy' => $legacyProxy];
        }

        return $this->resolveSystemFallback($targetHost);
    }

    /**
     * @return array{proxy: string}
     */
    private function resolveFromProxyEntity(Proxy $proxy, string $targetHost): array
    {
        if (!$proxy->isEnabled()) {
            return ['proxy' => ''];
        }

        if ($this->isHostExcluded($targetHost, $proxy->getNoProxy())) {
            return ['proxy' => ''];
        }

        return ['proxy' => $this->buildProxyUrl($proxy)];
    }

    /**
     * @return array{proxy: string}
     */
    public function resolveSystemFallback(string $targetHost): array
    {
        if ($this->isHostExcluded($targetHost, null)) {
            return ['proxy' => ''];
        }

        $envProxy = $_SERVER['HTTP_PROXY'] ?? $_SERVER['http_proxy'] ?? $_ENV['HTTP_PROXY'] ?? $_ENV['http_proxy'] ?? (getenv('HTTP_PROXY') ?: (getenv('http_proxy') ?: null));

        if (!empty($envProxy)) {
            return ['proxy' => (string) $envProxy];
        }

        return ['proxy' => ''];
    }

    public function isHostExcluded(string $host, ?string $noProxyList = null): bool
    {
        $host = strtolower(trim(preg_replace('#^https?://#', '', rtrim($host, '/'))));
        if ('' === $host) {
            return true;
        }

        // Strip port if present in host
        if (str_contains($host, ':')) {
            $parts = explode(':', $host, 2);
            $host = $parts[0];
        }

        // Local and company internal domains
        if ('localhost' === $host || '127.0.0.1' === $host || str_contains($host, 'bm-energies.com') || str_ends_with($host, '.local') || str_ends_with($host, '.localhost')) {
            return true;
        }

        // Check proxy's specific noProxy list
        if (null !== $noProxyList && '' !== trim($noProxyList)) {
            if ($this->matchNoProxy($host, $noProxyList)) {
                return true;
            }
        }

        // Check system NO_PROXY
        $systemNoProxy = $_SERVER['NO_PROXY'] ?? $_SERVER['no_proxy'] ?? $_ENV['NO_PROXY'] ?? $_ENV['no_proxy'] ?? (getenv('NO_PROXY') ?: (getenv('no_proxy') ?: ''));
        if ('' !== $systemNoProxy) {
            if ($this->matchNoProxy($host, (string) $systemNoProxy)) {
                return true;
            }
        }

        return false;
    }

    private function matchNoProxy(string $host, string $noProxy): bool
    {
        $entries = array_map('trim', explode(',', $noProxy));
        foreach ($entries as $entry) {
            if ('' === $entry) {
                continue;
            }
            $cleanEntry = strtolower(ltrim($entry, '*.'));
            if ('' === $cleanEntry) {
                continue;
            }
            if ($host === $cleanEntry || str_ends_with($host, '.'.$cleanEntry) || str_contains($host, $cleanEntry)) {
                return true;
            }
        }

        return false;
    }

    public function buildProxyUrl(Proxy $proxy): string
    {
        $url = trim($proxy->getUrl());
        $username = $proxy->getUsername();
        $password = $proxy->getPassword();

        if (null === $username || '' === $username) {
            return $url;
        }

        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['host'])) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        $userInfo = rawurlencode($username);
        if (null !== $password && '' !== $password) {
            $userInfo .= ':'.rawurlencode($password);
        }

        return sprintf('%s://%s@%s%s%s%s', $scheme, $userInfo, $host, $port, $path, $query);
    }
}
