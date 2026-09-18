<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class LdapUser extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $ldapUid = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $distinguishedName = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $syncedAt = null;

    public function getLdapUid(): ?string
    {
        return $this->ldapUid;
    }

    public function setLdapUid(?string $ldapUid): static
    {
        $this->ldapUid = $ldapUid;

        return $this;
    }

    public function getDistinguishedName(): ?string
    {
        return $this->distinguishedName;
    }

    public function setDistinguishedName(?string $distinguishedName): static
    {
        $this->distinguishedName = $distinguishedName;

        return $this;
    }

    public function getSyncedAt(): ?\DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(?\DateTimeImmutable $syncedAt): static
    {
        $this->syncedAt = $syncedAt;

        return $this;
    }
}
