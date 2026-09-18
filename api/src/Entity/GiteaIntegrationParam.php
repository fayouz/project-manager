<?php

declare(strict_types=1);

namespace App\Entity;

class GiteaIntegrationParam extends AbstractIntegrationParam
{
    private ?string $repository = null;
    private ?string $branch = null;

    public function getType(): string
    {
        return 'gitea';
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function setRepository(?string $repository): static
    {
        $this->repository = $repository !== null && trim($repository) !== '' ? trim($repository) : null;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(?string $branch): static
    {
        $this->branch = $branch !== null && trim($branch) !== '' ? trim($branch) : null;

        return $this;
    }

    public function toArray(): array
    {
        $data = $this->extra;
        if ($this->repository !== null) {
            $data['repository'] = $this->repository;
        }
        if ($this->branch !== null) {
            $data['branch'] = $this->branch;
        }

        return $data;
    }

    public static function fromArray(array $parameters): static
    {
        $param = new self();
        if (isset($parameters['repository'])) {
            $param->setRepository($parameters['repository']);
        }
        if (isset($parameters['branch'])) {
            $param->setBranch($parameters['branch']);
        }

        $extra = $parameters;
        unset($extra['repository'], $extra['branch']);
        $param->setExtra($extra);

        return $param;
    }

    public function validate(): array
    {
        $errors = [];
        if ($this->repository === null || $this->repository === '') {
            $errors['repository'] = "Le dépôt Gitea (propriétaire/dépôt) est requis.";
        }

        return $errors;
    }

    public function getTargetDisplay(): string
    {
        if ($this->repository !== null && $this->branch !== null) {
            return sprintf('%s (%s)', $this->repository, $this->branch);
        }
        if ($this->repository !== null) {
            return $this->repository;
        }

        return 'Non configuré';
    }
}
