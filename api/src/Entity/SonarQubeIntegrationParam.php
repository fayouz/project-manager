<?php

declare(strict_types=1);

namespace App\Entity;

class SonarQubeIntegrationParam extends AbstractIntegrationParam
{
    private ?string $projectKey = null;

    public function getType(): string
    {
        return 'sonarqube';
    }

    public function getProjectKey(): ?string
    {
        return $this->projectKey;
    }

    public function setProjectKey(?string $projectKey): static
    {
        $this->projectKey = $projectKey !== null && trim($projectKey) !== '' ? trim($projectKey) : null;

        return $this;
    }

    public function toArray(): array
    {
        $data = $this->extra;
        if ($this->projectKey !== null) {
            $data['project_key'] = $this->projectKey;
        }

        return $data;
    }

    public static function fromArray(array $parameters): static
    {
        $param = new self();
        if (isset($parameters['project_key'])) {
            $param->setProjectKey($parameters['project_key']);
        }

        $extra = $parameters;
        unset($extra['project_key']);
        $param->setExtra($extra);

        return $param;
    }

    public function validate(): array
    {
        $errors = [];
        if ($this->projectKey === null || $this->projectKey === '') {
            $errors['project_key'] = "La clé de projet SonarQube (project_key) est requise.";
        }

        return $errors;
    }

    public function getTargetDisplay(): string
    {
        return $this->projectKey ?? 'Non configuré';
    }
}
