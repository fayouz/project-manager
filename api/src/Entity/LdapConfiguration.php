<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LdapConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\LdapTestController;

#[ORM\Entity(repositoryClass: LdapConfigurationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Post(
            name: 'test_connection',
            uriTemplate: '/ldap_configurations/test',
            controller: LdapTestController::class,
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['ldap_config:read']],
    denormalizationContext: ['groups' => ['ldap_config:write']],
)]
class LdapConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ldap_config:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private bool $enabled = false;

    #[ORM\Column(length: 255)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $host = null;

    #[ORM\Column]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?int $port = 389;

    #[ORM\Column(length: 255)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $baseDn = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $bindDn = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ldap_config:write'])]
    private ?string $bindPassword = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $imageAttribute = 'jpegPhoto';

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?array $attributeMapping = [
        'image' => 'jpegPhoto',
        'email' => 'mail',
        'username' => 'sAMAccountName',
    ];

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $searchFilter = null;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $searchFilterRegex = null;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?string $queryRegex = null;

    /**
     * @var array<int, array{attribute: string, pattern: string}>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['ldap_config:read', 'ldap_config:write'])]
    private ?array $searchFilters = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getBaseDn(): ?string
    {
        return $this->baseDn;
    }

    public function setBaseDn(string $baseDn): static
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    public function getBindDn(): ?string
    {
        return $this->bindDn;
    }

    public function setBindDn(?string $bindDn): static
    {
        $this->bindDn = $bindDn;

        return $this;
    }

    public function getBindPassword(): ?string
    {
        return $this->bindPassword;
    }

    public function setBindPassword(?string $bindPassword): static
    {
        if ($bindPassword !== null && $bindPassword !== '') {
            $this->bindPassword = $bindPassword;
        }

        return $this;
    }

    public function getImageAttribute(): ?string
    {
        return $this->imageAttribute ?? $this->attributeMapping['image'] ?? 'jpegPhoto';
    }

    public function setImageAttribute(?string $imageAttribute): static
    {
        $this->imageAttribute = $imageAttribute;
        if (!is_array($this->attributeMapping)) {
            $this->attributeMapping = [];
        }
        $this->attributeMapping['image'] = $imageAttribute;

        return $this;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->attributeMapping['email'] ?? 'mail';
    }

    public function setEmailAttribute(?string $emailAttribute): static
    {
        if (!is_array($this->attributeMapping)) {
            $this->attributeMapping = [];
        }
        $this->attributeMapping['email'] = $emailAttribute;

        return $this;
    }

    public function getUsernameAttribute(): ?string
    {
        return $this->attributeMapping['username'] ?? 'sAMAccountName';
    }

    public function setUsernameAttribute(?string $usernameAttribute): static
    {
        if (!is_array($this->attributeMapping)) {
            $this->attributeMapping = [];
        }
        $this->attributeMapping['username'] = $usernameAttribute;

        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function getAttributeMapping(): ?array
    {
        $mapping = $this->attributeMapping ?? [];
        if (!isset($mapping['image']) && $this->imageAttribute) {
            $mapping['image'] = $this->imageAttribute;
        }

        return $mapping;
    }

    /**
     * @param array<string, string>|null $attributeMapping
     */
    public function setAttributeMapping(?array $attributeMapping): static
    {
        $this->attributeMapping = $attributeMapping;
        if (isset($attributeMapping['image']) && is_string($attributeMapping['image'])) {
            $this->imageAttribute = $attributeMapping['image'];
        }

        return $this;
    }

    public function getSearchFilter(): ?string
    {
        return $this->searchFilter;
    }

    public function setSearchFilter(?string $searchFilter): static
    {
        $this->searchFilter = $searchFilter;

        return $this;
    }

    public function getSearchFilterRegex(): ?string
    {
        return $this->searchFilterRegex;
    }

    public function setSearchFilterRegex(?string $searchFilterRegex): static
    {
        $this->searchFilterRegex = $searchFilterRegex;

        return $this;
    }

    public function getQueryRegex(): ?string
    {
        return $this->queryRegex;
    }

    public function setQueryRegex(?string $queryRegex): static
    {
        $this->queryRegex = $queryRegex;

        return $this;
    }

    /**
     * @return array<int, array{attribute: string, pattern: string}>|null
     */
    public function getSearchFilters(): ?array
    {
        return $this->searchFilters ?? [];
    }

    /**
     * @param array<int, array{attribute: string, pattern: string}>|null $searchFilters
     */
    public function setSearchFilters(?array $searchFilters): static
    {
        $this->searchFilters = $searchFilters ?? [];

        return $this;
    }
}
