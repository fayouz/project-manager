---
title: Démarrage rapide
description: Installer et lancer Project Manager en local avec Docker Compose.
navigation:
  title: Démarrage rapide
  icon: i-heroicons-rocket-launch
---

## Prérequis

- Docker et Docker Compose
- (optionnel) `pnpm` si vous travaillez sur le front en dehors du conteneur

## Lancer le projet

Depuis la racine du dépôt :

```bash
docker compose up -d
```

Le frontend est alors accessible sur `http://local-project-manager.localhost/`.

## Travailler sur le frontend

```bash
docker compose exec front pnpm dev
```

## Générer un CRUD pour une nouvelle ressource API Platform

Le projet utilise un générateur client personnalisé (`NuxtUi4Generator`) pour créer automatiquement les composants, stores Pinia et types TypeScript d'une ressource :

```bash
docker compose exec front pnpm client-generator -r <NomRessource>
```

Consultez la page [Générateur de client](/guide/essentials/client-generator) pour plus de détails.
