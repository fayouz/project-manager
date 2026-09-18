<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\ProjectIntegrationHealthController;
use App\Controller\ProjectIntegrationLiveDataController;
use App\Repository\ProjectIntegrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectIntegrationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
        new Post(
            name: 'project_integration_health',
            uriTemplate: '/project_integrations/{id}/health',
            controller: ProjectIntegrationHealthController::class,
            read: true,
            status: 200
        ),
        new Get(
            name: 'project_integration_health_get',
            uriTemplate: '/project_integrations/{id}/health',
            controller: ProjectIntegrationHealthController::class,
            read: true,
            status: 200
        ),
        new Get(
            name: 'project_integration_live_data',
            uriTemplate: '/project_integrations/{id}/live-data',
            controller: ProjectIntegrationLiveDataController::class,
            read: true,
            status: 200
        ),
    ],
    normalizationContext: ['groups' => ['project_integration:read', 'integration:read']],
    denormalizationContext: ['groups' => ['project_integration:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'project' => 'exact',
    'project.id' => 'exact',
    'integration' => 'exact',
    'integration.id' => 'exact',
    'integration.type' => 'exact',
])]
class ProjectIntegration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['project_integration:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectIntegrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['project_integration:read', 'project_integration:write'])]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Integration::class, inversedBy: 'projectIntegrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['project_integration:read', 'project_integration:write'])]
    private ?Integration $integration = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['project_integration:read', 'project_integration:write'])]
    private array $parameters = [];

    #[ORM\Column(length: 30, options: ['default' => 'unknown'])]
    #[Groups(['project_integration:read'])]
    private string $status = 'unknown';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['project_integration:read'])]
    private ?string $statusMessage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['project_integration:read'])]
    private ?\DateTimeImmutable $lastCheckedAt = null;

    #[ORM\Column]
    #[Groups(['project_integration:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['project_integration:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->parameters = [];
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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getIntegration(): ?Integration
    {
        return $this->integration;
    }

    public function setIntegration(?Integration $integration): static
    {
        $this->integration = $integration;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

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

    public function getIntegrationParam(): ?IntegrationParamInterface
    {
        $type = $this->integration?->getType();
        if ($type === null || $type === '') {
            return null;
        }

        return IntegrationParamFactory::create($type, $this->parameters);
    }

    public function setIntegrationParam(IntegrationParamInterface $param): static
    {
        $this->parameters = $param->toArray();

        return $this;
    }

    #[Groups(['project_integration:read'])]
    public function getTargetDisplay(): ?string
    {
        return $this->getIntegrationParam()?->getTargetDisplay();
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
}
