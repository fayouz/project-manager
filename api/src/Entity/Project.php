<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Organisation::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organisation $organisation = null;

    /**
     * @var Collection<int, ProjectIntegration>
     */
    #[ORM\OneToMany(targetEntity: ProjectIntegration::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projectIntegrations;

    /**
     * @var Collection<int, ProjectMember>
     */
    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $teams;

    /**
     * @var Collection<int, Staging>
     */
    #[ORM\OneToMany(targetEntity: Staging::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $stagings;

    public function __construct()
    {
        $this->projectIntegrations = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->stagings = new ArrayCollection();
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

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<int, ProjectIntegration>
     */
    public function getProjectIntegrations(): Collection
    {
        return $this->projectIntegrations;
    }

    public function addProjectIntegration(ProjectIntegration $projectIntegration): static
    {
        if (!$this->projectIntegrations->contains($projectIntegration)) {
            $this->projectIntegrations->add($projectIntegration);
            $projectIntegration->setProject($this);
        }

        return $this;
    }

    public function removeProjectIntegration(ProjectIntegration $projectIntegration): static
    {
        if ($this->projectIntegrations->removeElement($projectIntegration)) {
            if ($projectIntegration->getProject() === $this) {
                $projectIntegration->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ProjectMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setProject($this);
        }

        return $this;
    }

    public function removeMember(ProjectMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getProject() === $this) {
                $member->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setProject($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            if ($team->getProject() === $this) {
                $team->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Staging>
     */
    public function getStagings(): Collection
    {
        return $this->stagings;
    }

    public function addStaging(Staging $staging): static
    {
        if (!$this->stagings->contains($staging)) {
            $this->stagings->add($staging);
            $staging->setProject($this);
        }

        return $this;
    }

    public function removeStaging(Staging $staging): static
    {
        if ($this->stagings->removeElement($staging)) {
            if ($staging->getProject() === $this) {
                $staging->setProject(null);
            }
        }

        return $this;
    }
}
