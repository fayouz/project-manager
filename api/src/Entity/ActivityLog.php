<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_activity_log_entity')]
#[ORM\Index(columns: ['created_at'], name: 'idx_activity_log_created_at')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['activity_log:read']],
    order: ['createdAt' => 'DESC'],
)]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['activity_log:read'])]
    private string $entityType;

    #[ORM\Column(length: 255)]
    #[Groups(['activity_log:read'])]
    private string $entityId;

    #[ORM\Column(length: 255)]
    #[Groups(['activity_log:read'])]
    private string $entityLabel;

    #[ORM\Column(length: 20)]
    #[Groups(['activity_log:read'])]
    private string $action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['activity_log:read'])]
    private ?User $actor = null;

    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityLabel(): string
    {
        return $this->entityLabel;
    }

    public function setEntityLabel(string $entityLabel): static
    {
        $this->entityLabel = $entityLabel;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
