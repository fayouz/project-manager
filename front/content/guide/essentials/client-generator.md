---
title: Générateur de client
description: Le générateur NuxtUi4Generator produit les composants CRUD, stores Pinia et types TypeScript pour chaque ressource API Platform.
navigation:
  title: Générateur de client
  icon: i-heroicons-cog-6-tooth
---

## Objectif

`NuxtUi4Generator` génère, pour une ressource `<Resource>` exposée par l'API Platform :

- des composants Vue 3 / Nuxt UI v4 (`<Resource>Create.vue`, `<Resource>Update.vue`, `<Resource>Show.vue`, `<Resource>List.vue`, `<Resource>Form.vue`),
- des stores Pinia typés (`create`, `delete`, `list`, `show`, `update`),
- un type TypeScript étendant `Item`.

Il **ne génère pas de pages** : à vous de créer `app/pages/<resources>.vue` et de brancher les modales.

## Commande

```bash
docker compose exec front pnpm client-generator -r <NomRessource>
```

## Fichiers du générateur

- Classe : `front/generator/NuxtUi4Generator.js`
- Templates Handlebars : `front/generator/templates/nuxtui4/`
