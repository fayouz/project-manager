<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
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
        $this->bindPassword = $bindPassword;

        return $this;
    }
}
