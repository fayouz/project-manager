<?php

declare(strict_types=1);

namespace App\Entity;

interface IntegrationParamInterface
{
    /**
     * Identifiant du type de connecteur (ex: 'mantis', 'gitea', 'sonarqube', 'jenkins').
     */
    public function getType(): string;

    /**
     * Convertit les paramètres sous forme de tableau associatif sérialisable en JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Instancie l'entité de paramètre depuis un tableau.
     *
     * @param array<string, mixed> $parameters
     */
    public static function fromArray(array $parameters): static;

    /**
     * Valide la complétude et la conformité des paramètres.
     *
     * @return array<string, string> Liste des erreurs de validation (vide si valide)
     */
    public function validate(): array;

    /**
     * Indique si les paramètres minimaux requis sont renseignés.
     */
    public function isValid(): bool;

    /**
     * Libellé lisible de la ressource cible (ex: "Nom (#ID)" ou "Dépôt (branche)").
     */
    public function getTargetDisplay(): string;
}
