# Page Changelog (`/changelog`)

## 1. Contexte & objectif

Le frontend (`front/`) expose une page **`/changelog`** inspirée du template officiel [`nuxt-ui-templates/changelog`](https://github.com/nuxt-ui-templates/changelog), qui affiche l'historique des évolutions de l'application sous forme de timeline verticale avec les composants **Nuxt UI v4** `UChangelogVersions` / `UChangelogVersion`.

À la différence du template officiel — qui récupère les releases d'un dépôt GitHub public via l'API `ungh.cc` — cette page s'appuie sur du contenu **Markdown local** géré par **Nuxt Content** (comme la section [`/guide`](frontend-guide-docs.md)), car le projet n'a pas de releases GitHub publiques.

## 2. Emplacement des fichiers

| Élément | Chemin |
|---|---|
| Configuration de la collection | `front/content.config.ts` (collection `changelog`) |
| Contenu Markdown | `front/content/changelog/*.md` |
| Page | `front/app/pages/changelog.vue` |

## 3. Fonctionnement

- `content.config.ts` déclare une collection `changelog` (`type: 'page'`, `source: 'changelog/**/*.md'`) avec un schéma dédié : `date` (obligatoire), `badge` (optionnel), `image` (optionnel), `authors` (optionnel).
- Chaque fichier Markdown sous `front/content/changelog/` correspond à une entrée de changelog. Le corps Markdown (titres, listes, gras...) constitue le détail de l'entrée.
- `app/pages/changelog.vue` charge toutes les entrées via `queryCollection('changelog').all()`, les trie par `date` décroissante, puis les affiche avec `UChangelogVersions` / `UChangelogVersion` : le titre, la description et le badge proviennent du frontmatter, le corps est rendu via `<ContentRenderer>` dans le slot `#body`.
- La page réutilise le layout `guide` (en-tête, recherche, bouton retour à l'application) pour rester cohérente visuellement avec `/guide`.
- La route `/changelog` (et ses sous-routes) est ajoutée aux `publicRoutes` de `app/middleware/auth.global.ts` : elle est donc accessible sans authentification.

## 4. Ajouter une nouvelle entrée de changelog

1. Créer un fichier Markdown sous `front/content/changelog/`, nommé par convention `AAAA-MM-JJ-slug.md` (le tri se fait sur le champ `date`, pas sur le nom de fichier).
2. Renseigner le frontmatter :
   ```md
   ---
   title: Titre de l'entrée
   description: Description courte affichée sous le titre.
   date: 2026-09-23
   badge: Nouveauté
   ---
   ```
3. Rédiger le détail de l'évolution en Markdown standard (listes, gras, liens...).
4. L'entrée apparaît automatiquement sur `/changelog`, triée par date.

## 5. Navigation

Un lien "Changelog" a été ajouté :
- dans la barre latérale du tableau de bord (`app/layouts/dashboard.vue`, section `navItems`) ;
- dans la barre de navigation de la page d'accueil publique (`app/pages/index.vue`).

## 6. Dépendance

La page nécessite `@nuxt/ui` en version `^4.11.2` ou supérieure (les composants `UChangelogVersions` / `UChangelogVersion` n'existent pas dans les versions antérieures). La dépendance a été mise à jour dans `front/package.json`.
