<?php

declare(strict_types=1);

namespace App\Entity;

class GenericIntegrationParam extends AbstractIntegrationParam
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly string $type,
        private array $parameters = []
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return array_merge($this->extra, $this->parameters);
    }

    public static function fromArray(array $parameters): static
    {
        return new self($parameters['type'] ?? 'generic', $parameters);
    }

    public function validate(): array
    {
        return [];
    }

    public function getTargetDisplay(): string
    {
        $first = reset($this->parameters);

        return is_scalar($first) ? (string) $first : 'Personnalisé';
    }
}
