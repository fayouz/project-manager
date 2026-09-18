<?php

declare(strict_types=1);

namespace App\Entity;

class MantisIntegrationParam extends AbstractIntegrationParam
{
    private ?string $projectId = null;
    private ?string $projectName = null;

    public function getType(): string
    {
        return 'mantis';
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function setProjectId(string|int|null $projectId): static
    {
        $this->projectId = $projectId !== null && (string) $projectId !== '' ? trim((string) $projectId) : null;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(?string $projectName): static
    {
        $this->projectName = $projectName !== null && trim($projectName) !== '' ? trim($projectName) : null;

        return $this;
    }

    public function toArray(): array
    {
        $data = $this->extra;
        if ($this->projectId !== null) {
            $data['project_id'] = $this->projectId;
        }
        if ($this->projectName !== null) {
            $data['project_name'] = $this->projectName;
        }

        return $data;
    }

    public static function fromArray(array $parameters): static
    {
        $param = new self();
        if (isset($parameters['project_id'])) {
            $param->setProjectId($parameters['project_id']);
        }
        if (isset($parameters['project_name'])) {
            $param->setProjectName($parameters['project_name']);
        }

        $extra = $parameters;
        unset($extra['project_id'], $extra['project_name']);
        $param->setExtra($extra);

        return $param;
    }

    public function validate(): array
    {
        $errors = [];
        if ($this->projectId === null || $this->projectId === '') {
            $errors['project_id'] = "L'identifiant du projet Mantis est requis.";
        }

        return $errors;
    }

    public function getTargetDisplay(): string
    {
        if ($this->projectName !== null && $this->projectId !== null) {
            return sprintf('%s (#%s)', $this->projectName, $this->projectId);
        }
        if ($this->projectName !== null) {
            return $this->projectName;
        }
        if ($this->projectId !== null) {
            return '#' . $this->projectId;
        }

        return 'Non configuré';
    }
}
