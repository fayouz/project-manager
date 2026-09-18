<?php

declare(strict_types=1);

namespace App\Entity;

abstract class AbstractIntegrationParam implements IntegrationParamInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $extra = [];

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    public function isValid(): bool
    {
        return count($this->validate()) === 0;
    }
}
