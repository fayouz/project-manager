<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\IntegrationProjectsController;
use App\Controller\IntegrationTestController;
use App\Repository\IntegrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IntegrationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete(),
        new Patch(),
        new Post(
            name: 'integration_test_connection',
            uriTemplate: '/integrations/{id}/test',
            controller: IntegrationTestController::class,
            read: true,
            status: 200
        ),
        new Post(
            name: 'integration_test_connection_transient',
            uriTemplate: '/integrations/test',
            controller: IntegrationTestController::class,
            read: false,
            status: 200
        ),
        new Get(
            name: 'integration_get_projects',
            uriTemplate: '/integrations/{id}/projects',
            controller: IntegrationProjectsController::class,
            read: true,
            status: 200
        ),
    ],
    normalizationContext: ['groups' => ['integration:read']],
    denormalizationContext: ['groups' => ['integration:write']]
)]
class Integration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['integration:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['integration:read', 'integration:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Groups(['integration:read', 'integration:write'])]
    private ?string $type = null;

    #[ORM\Column]
    #[Groups(['integration:read', 'integration:write'])]
    private bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'integrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['integration:read', 'integration:write'])]
    #[Assert\NotNull]
    private ?Server $server = null;

    #[ORM\ManyToOne(targetEntity: Proxy::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['integration:read', 'integration:write'])]
    private ?Proxy $proxy = null;

    #[ORM\Column(length: 30)]
    #[Groups(['integration:read'])]
    private string $status = 'unknown';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['integration:read'])]
    private ?string $statusMessage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['integration:read'])]
    private ?\DateTimeImmutable $lastCheckedAt = null;

    #[ORM\Column]
    #[Groups(['integration:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['integration:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ProjectIntegration>
     */
    #[ORM\OneToMany(targetEntity: ProjectIntegration::class, mappedBy: 'integration', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projectIntegrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'unknown';
        $this->enabled = true;
        $this->projectIntegrations = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
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

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function getProxy(): ?Proxy
    {
        return $this->proxy;
    }

    public function setProxy(?Proxy $proxy): static
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): static
    {
        $this->statusMessage = $statusMessage;

        return $this;
    }

    public function getLastCheckedAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckedAt;
    }

    public function setLastCheckedAt(?\DateTimeImmutable $lastCheckedAt): static
    {
        $this->lastCheckedAt = $lastCheckedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ProjectIntegration>
     */
    public function getProjectIntegrations(): Collection
    {
        return $this->projectIntegrations;
    }
}
