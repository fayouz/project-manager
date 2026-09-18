<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OrganisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganisationRepository::class)]
#[ApiResource]
class Organisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'organisation', targetEntity: Project::class)]
    private Collection $projects;

    /**
     * @var Collection<int, OrganisationMember>
     */
    #[ORM\OneToMany(targetEntity: OrganisationMember::class, mappedBy: 'organisation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->members = new ArrayCollection();
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

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    /**
     * @return Collection<int, OrganisationMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(OrganisationMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setOrganisation($this);
        }

        return $this;
    }

    public function removeMember(OrganisationMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getOrganisation() === $this) {
                $member->setOrganisation(null);
            }
        }

        return $this;
    }
}
