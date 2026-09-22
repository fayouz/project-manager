<?php

declare(strict_types=1);

namespace App\Entity;

class NexusIntegrationParam extends AbstractIntegrationParam
{
    private ?string $repository = null;
    private ?string $group = null;
    private ?string $format = null;

    public function getType(): string
    {
        return 'nexus';
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

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): static
    {
        $this->group = $group !== null && trim($group) !== '' ? trim($group) : null;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): static
    {
        $this->format = $format !== null && trim($format) !== '' ? trim($format) : null;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->extra;
        if ($this->repository !== null) {
            $data['repository'] = $this->repository;
        }
        if ($this->group !== null) {
            $data['group'] = $this->group;
        }
        if ($this->format !== null) {
            $data['format'] = $this->format;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function fromArray(array $parameters): static
    {
        $param = new self();
        if (isset($parameters['repository'])) {
            $param->setRepository((string) $parameters['repository']);
        }
        if (isset($parameters['group'])) {
            $param->setGroup((string) $parameters['group']);
        }
        if (isset($parameters['format'])) {
            $param->setFormat((string) $parameters['format']);
        }

        $extra = $parameters;
        unset($extra['repository'], $extra['group'], $extra['format']);
        $param->setExtra($extra);

        return $param;
    }

    /**
     * @return array<string, string>
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->repository === null || $this->repository === '') {
            $errors['repository'] = 'Le nom du dépôt Nexus (repository) est requis.';
        }

        return $errors;
    }

    public function getTargetDisplay(): string
    {
        if ($this->repository !== null && $this->group !== null) {
            return sprintf('%s (%s)', $this->repository, $this->group);
        }
        if ($this->repository !== null) {
            return $this->repository;
        }

        return 'Non configuré';
    }
}
