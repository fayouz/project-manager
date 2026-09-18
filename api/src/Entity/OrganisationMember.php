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
use App\Enum\OrganisationRole;
use App\Repository\OrganisationMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganisationMemberRepository::class)]
#[ORM\Table(name: 'organisation_member')]
#[ORM\UniqueConstraint(name: 'UNIQ_ORG_USER', fields: ['organisation', 'user'])]
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
    normalizationContext: ['groups' => ['organisation_member:read', 'user:read']],
    denormalizationContext: ['groups' => ['organisation_member:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'organisation' => 'exact',
    'organisation.id' => 'exact',
    'user' => 'exact',
    'user.id' => 'exact',
    'role' => 'exact',
])]
class OrganisationMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['organisation_member:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organisation::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['organisation_member:read', 'organisation_member:write'])]
    private ?Organisation $organisation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organisationMembers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['organisation_member:read', 'organisation_member:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 30, enumType: OrganisationRole::class)]
    #[Assert\NotNull]
    #[Groups(['organisation_member:read', 'organisation_member:write'])]
    private OrganisationRole $role = OrganisationRole::MEMBER;

    #[ORM\Column]
    #[Groups(['organisation_member:read'])]
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

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): static
    {
        $this->organisation = $organisation;

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

    public function getRole(): OrganisationRole
    {
        return $this->role;
    }

    public function setRole(OrganisationRole $role): static
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
