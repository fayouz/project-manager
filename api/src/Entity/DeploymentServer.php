<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DeploymentServerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeploymentServerRepository::class)]
#[ApiResource]
class DeploymentServer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webserverUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(nullable: true)]
    private ?int $port = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Staging>
     */
    #[ORM\OneToMany(targetEntity: Staging::class, mappedBy: 'deploymentServer')]
    private Collection $stagings;

    public function __construct()
    {
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

    public function getWebserverUrl(): ?string
    {
        return $this->webserverUrl;
    }

    public function setWebserverUrl(?string $webserverUrl): static
    {
        $this->webserverUrl = $webserverUrl;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $staging->setDeploymentServer($this);
        }

        return $this;
    }

    public function removeStaging(Staging $staging): static
    {
        if ($this->stagings->removeElement($staging)) {
            if ($staging->getDeploymentServer() === $this) {
                $staging->setDeploymentServer(null);
            }
        }

        return $this;
    }
}
