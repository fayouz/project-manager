<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\LdapConfiguration;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class LdapSearchFilterRegexTest extends TestCase
{
    private LdapSyncService $service;

    protected function setUp(): void
    {
        $ldapConfigRepo = $this->createMock(\App\Repository\LdapConfigurationRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = new NullLogger();

        $this->service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);
    }

    public function testMatchesRegexBasic(): void
    {
        $this->assertTrue($this->service->matchesRegex('^PINF\d+$', 'PINF14'));
        $this->assertFalse($this->service->matchesRegex('^PINF\d+$', 'ADMIN01'));
        $this->assertTrue($this->service->matchesRegex('^pinf\d+$', 'PINF14')); // Case-insensitive
    }

    public function testMatchesRegexWithDelimiters(): void
    {
        $this->assertTrue($this->service->matchesRegex('/^pin[a-z0-9]+$/i', 'PINF14'));
        $this->assertFalse($this->service->matchesRegex('/^pin[a-z0-9]+$/', 'PINF14')); // Case-sensitive delimiter
        $this->assertTrue($this->service->matchesRegex('#.*@bm-energies\.com$#i', 'user@bm-energies.com'));
    }

    public function testMatchesRegexEmailDomain(): void
    {
        $pattern = '.*@(bm-energies\.com|groupegdb\.local)$';
        $this->assertTrue($this->service->matchesRegex($pattern, 'fbouloussa-ext@bm-energies.com'));
        $this->assertTrue($this->service->matchesRegex($pattern, 'admin@groupegdb.local'));
        $this->assertFalse($this->service->matchesRegex($pattern, 'intruder@external.org'));
    }

    public function testMatchesRegexNegativeLookahead(): void
    {
        // Exclure les comptes admin / service
        $pattern = '^(?!adm-|svc-).*$';
        $this->assertTrue($this->service->matchesRegex($pattern, 'jdupont'));
        $this->assertFalse($this->service->matchesRegex($pattern, 'adm-jdupont'));
        $this->assertFalse($this->service->matchesRegex($pattern, 'svc-backup'));
    }

    public function testMatchesRegexInvalidSyntaxDoesNotCrash(): void
    {
        // Syntaxe invalide non fermée
        $this->assertTrue($this->service->matchesRegex('[a-z', 'test'));
    }

    public function testMatchesSearchFiltersWithoutFilters(): void
    {
        $config = new LdapConfiguration();
        $userData = [
            'username' => 'PINF14',
            'email' => 'pinf14@bm-energies.com',
            'displayName' => 'Farid Bouloussa',
        ];

        $this->assertTrue($this->service->matchesSearchFilters($config, $userData));
    }

    public function testMatchesSearchFiltersGlobalRegex(): void
    {
        $config = new LdapConfiguration();
        $config->setSearchFilterRegex('^PINF.*');

        $userData1 = [
            'username' => 'PINF14',
            'email' => 'pinf14@bm-energies.com',
        ];
        $userData2 = [
            'username' => 'jdupont',
            'email' => 'jdupont@bm-energies.com',
        ];

        $this->assertTrue($this->service->matchesSearchFilters($config, $userData1));
        $this->assertFalse($this->service->matchesSearchFilters($config, $userData2));
    }

    public function testMatchesSearchFiltersSpecificAttributes(): void
    {
        $config = new LdapConfiguration();
        $config->setSearchFilters([
            [
                'attribute' => 'mail',
                'pattern' => '.*@bm-energies\.com$',
            ],
            [
                'attribute' => 'sAMAccountName',
                'pattern' => '^PINF\d+$',
            ],
        ]);

        $validUser = [
            'username' => 'PINF14',
            'email' => 'fbouloussa@bm-energies.com',
        ];
        $wrongEmail = [
            'username' => 'PINF14',
            'email' => 'fbouloussa@other.com',
        ];
        $wrongUsername = [
            'username' => 'OTHER01',
            'email' => 'other@bm-energies.com',
        ];

        $this->assertTrue($this->service->matchesSearchFilters($config, $validUser));
        $this->assertFalse($this->service->matchesSearchFilters($config, $wrongEmail));
        $this->assertFalse($this->service->matchesSearchFilters($config, $wrongUsername));
    }

    public function testMatchesSearchFiltersDepartmentAndWildcard(): void
    {
        $config = new LdapConfiguration();
        $config->setSearchFilters([
            [
                'attribute' => 'department',
                'pattern' => '^(DSI|Informatique)$',
            ],
            [
                'attribute' => '*',
                'pattern' => 'Paris',
            ],
        ]);

        $matchingUser = [
            'username' => 'PINF14',
            'email' => 'pinf14@bm-energies.com',
            'department' => 'DSI',
            'dn' => 'cn=PINF14,ou=Paris,dc=local',
        ];
        $wrongDepartment = [
            'username' => 'PINF14',
            'email' => 'pinf14@bm-energies.com',
            'department' => 'RH',
            'dn' => 'cn=PINF14,ou=Paris,dc=local',
        ];
        $wrongLocation = [
            'username' => 'PINF14',
            'email' => 'pinf14@bm-energies.com',
            'department' => 'DSI',
            'dn' => 'cn=PINF14,ou=Lyon,dc=local',
        ];

        $this->assertTrue($this->service->matchesSearchFilters($config, $matchingUser));
        $this->assertFalse($this->service->matchesSearchFilters($config, $wrongDepartment));
        $this->assertFalse($this->service->matchesSearchFilters($config, $wrongLocation));
    }
}
