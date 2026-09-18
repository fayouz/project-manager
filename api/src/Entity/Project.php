<?php

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

    public function __construct()
    {
        $this->projectIntegrations = new ArrayCollection();
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
}
