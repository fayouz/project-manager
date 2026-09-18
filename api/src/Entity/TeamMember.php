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
use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\Table(name: 'team_member')]
#[ORM\UniqueConstraint(name: 'UNIQ_TEAM_USER', fields: ['team', 'user'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['team_member:read', 'user:read']],
    denormalizationContext: ['groups' => ['team_member:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'team' => 'exact',
    'team.id' => 'exact',
    'user' => 'exact',
    'user.id' => 'exact',
    'role' => 'exact',
])]
class TeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team_member:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['team_member:read', 'team_member:write'])]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['team_member:read', 'team_member:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 30, enumType: TeamRole::class)]
    #[Assert\NotNull]
    #[Groups(['team_member:read', 'team_member:write'])]
    private TeamRole $role = TeamRole::MEMBER;

    #[ORM\Column]
    #[Groups(['team_member:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->joinedAt === null) {
            $this->joinedAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): TeamRole
    {
        return $this->role;
    }

    public function setRole(TeamRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }
}
