# Guide pour les Agents : NuxtUi4Generator

Ce document détaille le fonctionnement, l'architecture et l'utilisation du générateur client personnalisé **`NuxtUi4Generator`** développé pour ce projet.

---

## 1. Contexte & Objectif

Dans ce projet, le frontend est propulsé par **Nuxt 4** et la bibliothèque de composants **Nuxt UI v4** (`@nuxt/ui`), connecté à un backend API Platform (PHP / Symfony avec le format Hydra / JSON-LD).

Le générateur standard `@api-platform/create-client` produit par défaut des templates anciens (Vue / Vuetify / Bootstrap / Nuxt 2/3 classique avec des pages générées rigides).  
**`NuxtUi4Generator`** a été créé pour :
- Produire des composants Vue 3 compatibles avec la syntaxe et les composants modernes de **Nuxt UI v4** (`UFormField`, `UInput`, `USelect`, `UButton`, `UCard`, `UAlert`, `UModal`, `UBadge`, `UIcon`).
- Être compatible avec l'arborescence Nuxt 4 (code source sous `front/app/`, alias de chemins `~/`).
- Fournir des stores Pinia typés et gérant le format JSON-LD / Hydra d'API Platform (support des clés préfixées `@id`, `@type` et des réponses normalisées).
- **Conserver la pleine maîtrise des pages** : le générateur crée les composants CRUD, les stores Pinia et les types TypeScript, mais **ne génère pas de pages** automatiquement dans `app/pages/`. Cela permet aux développeurs de concevoir librement leurs pages de dashboard, la navigation, le routing et l'intégration des modales.

---

## 2. Emplacement des fichiers du générateur

- **Classe du générateur** : `front/generator/NuxtUi4Generator.js`
- **Templates Handlebars Nuxt UI v4** : `front/generator/templates/nuxtui4/`
  - `components/foo/FooCreate.vue` : Formulaire de création encapsulé dans un `UCard`.
  - `components/foo/FooUpdate.vue` : Formulaire d'édition avec actions de modification et de suppression.
  - `components/foo/FooShow.vue` : Vue détaillée des attributs de l'entité.
  - `components/foo/FooList.vue` : Vue tabulaire avec actions (voir, modifier, supprimer).
  - `components/foo/FooForm.vue` : Formulaire réactif avec `UFormField` et gestion des erreurs de validation (`violations`).
  - `components/common/FormRepeater.vue` : Répéteur pour collections imbriquées.
  - `stores/foo/{create,delete,list,show,update}.ts` : Stores Pinia par ressource.
  - `composables/api.ts` : Fonctions d'appels API (fetch, create, update, delete).
  - `types/api.ts` : Types utilitaires pour les retours API.
  - `utils/resource.ts` : Fonctions d'extraction d'ID depuis les IRIs (`getIdFromIri`).

---

## 3. Commandes d'exécution

### Via le script npm (recommandé)

Depuis le conteneur Docker ou le répertoire `front/` :

```bash
# Dans le conteneur front via docker compose (à la racine du projet)
docker compose exec front pnpm client-generator -r <NomRessource>

# Ou directement dans le dossier front/ (si pnpm est installé localement)
cd front
pnpm client-generator -r <NomRessource>
```

#### Exemples :
```bash
# Pour générer le CRUD d'Organisation :
docker compose exec front pnpm client-generator -r Organisation

# Pour générer le CRUD de Project :
docker compose exec front pnpm client-generator -r Project
```

### Commande détaillée sous-jacente

Le script `pnpm client-generator` est défini dans `front/package.json` et exécute :

```bash
pnpm create-client http://local-project-manager.localhost/api app -g ./generator/NuxtUi4Generator.js -f hydra -r <NomRessource>
```

- `http://local-project-manager.localhost/api` : Point d'entrée de l'API Platform.
- `app` : Répertoire cible de destination (place les fichiers directement dans `front/app/`).
- `-g ./generator/NuxtUi4Generator.js` : Spécifie notre classe de générateur personnalisée.
- `-f hydra` : Format API Platform Hydra / JSON-LD.
- `-r <NomRessource>` : Nom de la classe/ressource exposée dans `docs.jsonld` (ex: `Project`, `Organisation`, `Server`).

---

## 4. Fichiers générés par ressource

Lors de la génération pour une ressource `<Resource>` (en minuscules `<resource>`) :

1. **Composants (`front/app/components/<resource>/`)** :
   - `<Resource>Create.vue`
   - `<Resource>Update.vue`
   - `<Resource>Show.vue`
   - `<Resource>List.vue`
   - `<Resource>Form.vue`
2. **Stores Pinia (`front/app/stores/<resource>/`)** :
   - `create.ts`
   - `delete.ts`
   - `list.ts`
   - `show.ts`
   - `update.ts`
3. **Types TypeScript (`front/app/types/<resource>.ts`)** :
   - Interface TypeScript étendant `Item` avec les champs typés selon le schéma OpenAPI/Hydra.

---

## 5. Comment créer et brancher la page Nuxt (`app/pages/<resources>.vue`)

Comme le générateur n'écrase ni ne crée de pages, la page principale doit être créée sous `front/app/pages/<resources>.vue`.

### Modèle standard recommandé

Le pattern utilisé dans ce projet (voir `app/pages/projects.vue` et `app/pages/organisations.vue`) comprend :
1. **Layout** : `definePageMeta({ layout: 'dashboard' })`.
2. **Barre de navigation** : `UDashboardNavbar` avec titre, badge de comptage et bouton d'action principale ("Nouveau").
3. **Barre d'outils** : Recherche textuelle réactive + bascule d'affichage Grille (`viewMode = 'grid'`) / Tableau (`viewMode = 'table'`).
4. **Vues** :
   - **Vue Grille** : Cartes `UCard` avec icône thématique, ID, détails rapides, et actions en pied de carte (voir, modifier, supprimer).
   - **Vue Tableau** : Intégration du composant généré `<ResourceList />`.
5. **Modales interactives (`UModal`)** :
   - Création : `<ResourceCreate :show-back="false" @created="onCreated" @cancel="isCreateModalOpen = false" />`
   - Modification : `<ResourceUpdate :id="selectedId" :item="selectedItem" :show-back="false" @updated="onUpdated" @deleted="onDeleted" @cancel="isEditModalOpen = false" />`
   - Détails : `<ResourceShow :id="selectedId" :item="selectedItem" :show-back="false" @back="isShowModalOpen = false" @edit="onEditFromShow" />`
6. **Synchronisation Mercure temps réel** : Appel à `useMercureList({ store: listStore, deleteStore: deleteStore })`.

### Ajout dans la barre latérale

Pour que la ressource apparaisse dans le menu latéral :
- Éditer `front/app/layouts/dashboard.vue`.
- Ajouter l'élément dans le tableau `navItems` :
  ```ts
  {
    label: 'Organisations',
    icon: 'i-heroicons-building-office-2',
    to: '/organisations'
  }
  ```

---

## 6. Permissions de fichiers sous Docker

Lors de l'exécution de `client-generator` via `docker compose exec front`, les nouveaux dossiers et fichiers peuvent appartenir à l'utilisateur `root` du conteneur.

Si vous devez éditer les fichiers depuis l'hôte par la suite, rétablissez les permissions avec :
```bash
docker compose exec front chown -R 1000:1000 /srv/app
```
