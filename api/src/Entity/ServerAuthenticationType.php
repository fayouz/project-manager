<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ServerAuthenticationTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServerAuthenticationTypeRepository::class)]
#[ApiResource]
class ServerAuthenticationType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['server:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read', 'server:write'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Server>
     */
    #[ORM\OneToMany(mappedBy: 'authenticationType', targetEntity: Server::class)]
    private Collection $servers;

    public function __construct()
    {
        $this->servers = new ArrayCollection();
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

    /**
     * @return Collection<int, Server>
     */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function addServer(Server $server): static
    {
        if (!$this->servers->contains($server)) {
            $this->servers->add($server);
            $server->setAuthenticationType($this);
        }

        return $this;
    }

    public function removeServer(Server $server): static
    {
        if ($this->servers->removeElement($server)) {
            if ($server->getAuthenticationType() === $this) {
                $server->setAuthenticationType(null);
            }
        }

        return $this;
    }
}
