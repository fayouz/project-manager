# Guide utilisateur (`/guide`) — Documentation Nuxt Content

## 1. Contexte & objectif

Le frontend (`front/`) expose désormais une section de documentation applicative sur la route **`/guide`**, construite avec **Nuxt Content v3** et les composants de contenu de **Nuxt UI v4** (`UPage`, `UPageAside`, `UContentNavigation`, `UContentToc`, `UContentSearch`, `ContentRenderer`).

Cette section reprend la structure et le fonctionnement du template officiel [`nuxt-ui-templates/docs`](https://github.com/nuxt-ui-templates/docs) : navigation latérale générée depuis l'arborescence de fichiers Markdown, sommaire (table des matières) à droite, recherche full-text (`Cmd+K`), et navigation "précédent / suivant" en bas de page.

Elle est **distincte** de la "Documentation API" (`/docs`), qui reste le lien externe vers la documentation Swagger/Hydra générée par API Platform.

## 2. Emplacement des fichiers

| Élément | Chemin |
|---|---|
| Configuration des collections | `front/content.config.ts` |
| Contenu Markdown | `front/content/guide/**/*.md` |
| Layout dédié | `front/app/layouts/guide.vue` |
| Page catch-all | `front/app/pages/guide/[...slug].vue` |

## 3. Fonctionnement

- `content.config.ts` déclare une collection `guide` (`type: 'page'`, `source: 'guide/**/*.md'`) avec un champ `navigation` optionnel (`title`, `icon`) permettant de personnaliser l'entrée de navigation d'une page.
- Chaque fichier Markdown sous `front/content/guide/` correspond à une route sous `/guide/...` (le fichier `index.md` d'un dossier correspond à la racine de ce dossier).
- `app/layouts/guide.vue` fournit l'en-tête (logo, recherche, lien GitHub du template source, retour à l'application) et injecte la navigation (`queryCollectionNavigation('guide')`) ainsi que l'index de recherche (`queryCollectionSearchSections('guide')`) via `provide`.
- `app/pages/guide/[...slug].vue` résout le chemin demandé, charge la page correspondante (`queryCollection('guide').path(...).first()`), affiche son contenu via `<ContentRenderer>`, son sommaire (`page.body.toc`) et la navigation précédent/suivant (`queryCollectionItemSurroundings`).
- La route `/guide` (et toutes ses sous-routes) est ajoutée aux `publicRoutes` de `app/middleware/auth.global.ts` : elle est donc accessible sans authentification, comme `/docs`.

## 4. Ajouter une nouvelle page de guide

1. Créer un fichier Markdown sous `front/content/guide/` (éventuellement dans un sous-dossier, ex. `front/content/guide/essentials/ma-page.md`).
2. Renseigner le frontmatter :
   ```md
   ---
   title: Titre de la page
   description: Description courte affichée dans l'en-tête et le SEO.
   navigation:
     title: Libellé court dans le menu
     icon: i-heroicons-...
   ---
   ```
3. Rédiger le contenu en Markdown standard (les titres `##`/`###` alimentent automatiquement le sommaire à droite).
4. La page est immédiatement accessible sur `/guide/<chemin-du-fichier>` (redémarrage du serveur de dev si le fichier a été ajouté à chaud sans watcher actif).

## 5. Installation de la dépendance

Le module `@nuxt/content` a été ajouté aux dépendances de `front/package.json` et aux `modules` de `front/nuxt.config.ts`. Après avoir récupéré ces changements, réinstallez les dépendances du conteneur frontend :

```bash
docker compose exec front pnpm install
```

## 6. Navigation

Un lien "Guide" a été ajouté :
- dans la barre latérale du tableau de bord (`app/layouts/dashboard.vue`, section `navItems`) ;
- dans la barre de navigation de la page d'accueil publique (`app/pages/index.vue`).
