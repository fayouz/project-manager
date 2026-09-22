<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Integration;
use App\Entity\Proxy;
use App\Entity\Server;
use App\Service\ProxyResolver;
use PHPUnit\Framework\TestCase;

class ProxyResolverTest extends TestCase
{
    private ProxyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ProxyResolver();
    }

    public function testIntegrationProxyOverridesServerProxy(): void
    {
        $serverProxy = new Proxy();
        $serverProxy->setName('Server Proxy');
        $serverProxy->setUrl('http://server-proxy:8080');
        $serverProxy->setEnabled(true);

        $integrationProxy = new Proxy();
        $integrationProxy->setName('Integration Proxy');
        $integrationProxy->setUrl('http://integration-proxy:8080');
        $integrationProxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-api.com');
        $server->setProxy($serverProxy);

        $integration = new Integration();
        $integration->setServer($server);
        $integration->setProxy($integrationProxy);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => 'http://integration-proxy:8080'], $options);
    }

    public function testInheritsServerProxyWhenIntegrationProxyIsNull(): void
    {
        $serverProxy = new Proxy();
        $serverProxy->setName('Server Proxy');
        $serverProxy->setUrl('http://server-proxy:8080');
        $serverProxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-api.com');
        $server->setProxy($serverProxy);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => 'http://server-proxy:8080'], $options);
    }

    public function testDisabledProxyReturnsDirect(): void
    {
        $proxy = new Proxy();
        $proxy->setUrl('http://corp-proxy:8080');
        $proxy->setEnabled(false);

        $server = new Server();
        $server->setHost('external-api.com');
        $server->setProxy($proxy);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => ''], $options);
    }

    public function testProxyExclusionViaNoProxyList(): void
    {
        $proxy = new Proxy();
        $proxy->setUrl('http://corp-proxy:8080');
        $proxy->setEnabled(true);
        $proxy->setNoProxy('my-internal-domain.net, .partner.org');

        $server = new Server();
        $server->setHost('api.my-internal-domain.net');
        $server->setProxy($proxy);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => ''], $options);
    }

    public function testInternalAndCompanyHostsBypassedAutomatically(): void
    {
        $proxy = new Proxy();
        $proxy->setUrl('http://corp-proxy:8080');
        $proxy->setEnabled(true);

        $server = new Server();
        $server->setHost('jenkins.bm-energies.com');
        $server->setProxy($proxy);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => ''], $options);
    }

    public function testProxyUrlWithCredentials(): void
    {
        $proxy = new Proxy();
        $proxy->setUrl('http://corp-proxy.com:8080');
        $proxy->setUsername('john.doe');
        $proxy->setPassword('p@ssw:rd!');
        $proxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-service.io');
        $server->setProxy($proxy);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(
            ['proxy' => 'http://john.doe:p%40ssw%3Ard%21@corp-proxy.com:8080'],
            $options
        );
    }

    public function testLegacyServerOptionsProxyDirect(): void
    {
        $server = new Server();
        $server->setHost('external-api.com');
        $server->setOptions(['proxy' => 'direct']);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => ''], $options);
    }

    public function testLegacyServerOptionsConfiguredProxy(): void
    {
        $server = new Server();
        $server->setHost('external-api.com');
        $server->setOptions(['proxy' => 'http://legacy-proxy:3128']);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => 'http://legacy-proxy:3128'], $options);
    }

    public function testLegacyServerOptionsBypassedForInternalHost(): void
    {
        $server = new Server();
        $server->setHost('gitea.bm-energies.com');
        $server->setOptions(['proxy' => 'http://legacy-proxy:3128']);

        $integration = new Integration();
        $integration->setServer($server);

        $options = $this->resolver->resolveProxyOptions($integration);

        $this->assertSame(['proxy' => ''], $options);
    }

    public function testSystemEnvironmentFallback(): void
    {
        $_SERVER['HTTP_PROXY'] = 'http://env-proxy:8888';

        try {
            $server = new Server();
            $server->setHost('external-api.com');

            $integration = new Integration();
            $integration->setServer($server);

            $options = $this->resolver->resolveProxyOptions($integration);

            $this->assertSame(['proxy' => 'http://env-proxy:8888'], $options);
        } finally {
            unset($_SERVER['HTTP_PROXY']);
        }
    }

    public function testResolveProxyForServerStandalone(): void
    {
        $proxy = new Proxy();
        $proxy->setUrl('http://proxy:8080');
        $proxy->setEnabled(true);

        $server = new Server();
        $server->setHost('external-site.com');
        $server->setProxy($proxy);

        $options = $this->resolver->resolveProxyForServer($server);

        $this->assertSame(['proxy' => 'http://proxy:8080'], $options);
    }
}
