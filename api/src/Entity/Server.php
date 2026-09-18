<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ServerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['server:read']],
    denormalizationContext: ['groups' => ['server:write']]
)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['server:read', 'integration:read', 'project_integration:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read', 'server:write', 'integration:read', 'project_integration:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read', 'server:write', 'integration:read', 'project_integration:read'])]
    private ?string $host = null;

    #[ORM\Column]
    #[Groups(['server:read', 'server:write', 'integration:read', 'project_integration:read'])]
    protected ?int $port = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read', 'server:write'])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:write'])]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['server:read', 'server:write', 'integration:read', 'project_integration:read'])]
    private array $options = [];

    #[ORM\ManyToOne(inversedBy: 'servers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['server:read', 'server:write'])]
    protected ?ServerType $type = null;

    #[ORM\ManyToOne(targetEntity: ServerAuthenticationType::class, inversedBy: 'servers')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['server:read', 'server:write', 'integration:read', 'project_integration:read'])]
    private ?ServerAuthenticationType $authenticationType = null;

    /**
     * @var Collection<int, Integration>
     */
    #[ORM\OneToMany(targetEntity: Integration::class, mappedBy: 'server')]
    private Collection $integrations;

    public function __construct()
    {
        $this->integrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getType(): ?ServerType
    {
        return $this->type;
    }

    public function setType(?ServerType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAuthenticationType(): ?ServerAuthenticationType
    {
        return $this->authenticationType;
    }

    public function setAuthenticationType(?ServerAuthenticationType $authenticationType): static
    {
        $this->authenticationType = $authenticationType;

        return $this;
    }

    /**
     * @return Collection<int, Integration>
     */
    public function getIntegrations(): Collection
    {
        return $this->integrations;
    }

    public function addIntegration(Integration $integration): static
    {
        if (!$this->integrations->contains($integration)) {
            $this->integrations->add($integration);
            $integration->setServer($this);
        }

        return $this;
    }

    public function removeIntegration(Integration $integration): static
    {
        if ($this->integrations->removeElement($integration)) {
            if ($integration->getServer() === $this) {
                $integration->setServer(null);
            }
        }

        return $this;
    }
}
