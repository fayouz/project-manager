---
title: Navigation & structure
description: Comprendre l'organisation des pages, layouts et composants du frontend.
navigation:
  title: Navigation & structure
  icon: i-heroicons-squares-2x2
---

## Layouts

- `layouts/default.vue` : page publique (landing).
- `layouts/dashboard.vue` : coquille applicative avec `UDashboardSidebar` et navigation latérale, utilisée par les pages protégées (organisations, projets, serveurs...).

## Pages

Chaque ressource métier possède un dossier de pages (`app/pages/<resource>/`) avec une liste (`index.vue`) et une page de détail (`[id].vue`).

## Ajouter une ressource au menu

Éditez `app/layouts/dashboard.vue` et ajoutez une entrée dans le tableau `navItems` :

```ts
{
  label: 'Organisations',
  icon: 'i-heroicons-building-office-2',
  to: '/organisations'
}
```
