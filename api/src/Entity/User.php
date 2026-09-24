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
use App\Repository\UserRepository;
use App\State\UserPasswordHasherProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['local' => LocalUser::class, 'ldap' => LdapUser::class])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(processor: UserPasswordHasherProcessor::class),
        new Get(),
        new Put(processor: UserPasswordHasherProcessor::class),
        new Patch(processor: UserPasswordHasherProcessor::class),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['user:read'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['user:write']],
)]
abstract class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'activity_log:read'])]
    protected ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    protected array $roles = [];

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['user:read', 'user:write', 'activity_log:read'])]
    protected ?string $username = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write', 'activity_log:read'])]
    protected ?string $displayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $department = null;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $managerDn = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subordinates')]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['user:read', 'user:write'])]
    #[MaxDepth(1)]
    protected ?User $manager = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'manager')]
    protected Collection $subordinates;

    /**
     * @var Collection<int, OrganisationMember>
     */
    #[ORM\OneToMany(targetEntity: OrganisationMember::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $organisationMembers;

    /**
     * @var Collection<int, ProjectMember>
     */
    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $projectMembers;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $teamMembers;

    public function __construct()
    {
        $this->organisationMembers = new ArrayCollection();
        $this->projectMembers = new ArrayCollection();
        $this->teamMembers = new ArrayCollection();
        $this->subordinates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getManagerDn(): ?string
    {
        return $this->managerDn;
    }

    public function setManagerDn(?string $managerDn): static
    {
        $this->managerDn = $managerDn;

        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSubordinates(): Collection
    {
        return $this->subordinates;
    }

    public function addSubordinate(User $subordinate): static
    {
        if (!$this->subordinates->contains($subordinate)) {
            $this->subordinates->add($subordinate);
            $subordinate->setManager($this);
        }

        return $this;
    }

    public function removeSubordinate(User $subordinate): static
    {
        if ($this->subordinates->removeElement($subordinate)) {
            if ($subordinate->getManager() === $this) {
                $subordinate->setManager(null);
            }
        }

        return $this;
    }

    #[Groups(['user:read'])]
    public function getAvatar(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'data:') || str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $mime = 'image/jpeg';
        if (str_starts_with($this->image, 'iVBORw0K')) {
            $mime = 'image/png';
        } elseif (str_starts_with($this->image, 'R0lGOD') || str_starts_with($this->image, 'R0lG')) {
            $mime = 'image/gif';
        } elseif (str_starts_with($this->image, 'UklGR')) {
            $mime = 'image/webp';
        } elseif (str_starts_with($this->image, 'PHN2Zy') || str_starts_with($this->image, 'PD94bWw')) {
            $mime = 'image/svg+xml';
        }

        return sprintf('data:%s;base64,%s', $mime, $this->image);
    }

    #[Groups(['user:read'])]
    public function getType(): string
    {
        return $this instanceof LocalUser ? 'local' : 'ldap';
    }

    #[Groups(['user:read'])]
    public function isLdap(): bool
    {
        return $this instanceof LdapUser;
    }

    #[Groups(['user:read'])]
    public function getIsLdap(): bool
    {
        return $this->isLdap();
    }

    public function setIsLdap(bool $isLdap): static
    {
        return $this;
    }

    public function setLdap(bool $isLdap): static
    {
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, OrganisationMember>
     */
    public function getOrganisationMembers(): Collection
    {
        return $this->organisationMembers;
    }

    public function addOrganisationMember(OrganisationMember $organisationMember): static
    {
        if (!$this->organisationMembers->contains($organisationMember)) {
            $this->organisationMembers->add($organisationMember);
            $organisationMember->setUser($this);
        }

        return $this;
    }

    public function removeOrganisationMember(OrganisationMember $organisationMember): static
    {
        if ($this->organisationMembers->removeElement($organisationMember)) {
            if ($organisationMember->getUser() === $this) {
                $organisationMember->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getProjectMembers(): Collection
    {
        return $this->projectMembers;
    }

    public function addProjectMember(ProjectMember $projectMember): static
    {
        if (!$this->projectMembers->contains($projectMember)) {
            $this->projectMembers->add($projectMember);
            $projectMember->setUser($this);
        }

        return $this;
    }

    public function removeProjectMember(ProjectMember $projectMember): static
    {
        if ($this->projectMembers->removeElement($projectMember)) {
            if ($projectMember->getUser() === $this) {
                $projectMember->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TeamMember>
     */
    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    public function addTeamMember(TeamMember $teamMember): static
    {
        if (!$this->teamMembers->contains($teamMember)) {
            $this->teamMembers->add($teamMember);
            $teamMember->setUser($this);
        }

        return $this;
    }

    public function removeTeamMember(TeamMember $teamMember): static
    {
        if ($this->teamMembers->removeElement($teamMember)) {
            if ($teamMember->getUser() === $this) {
                $teamMember->setUser(null);
            }
        }

        return $this;
    }
}
