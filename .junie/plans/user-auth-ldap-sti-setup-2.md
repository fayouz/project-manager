---
sessionId: session-260918-165146-104c
---

# Requirements

### Overview & Goals
Ce projet optimise l'expérience utilisateur et l'intégrité opérationnelle en branchant une gestion rigoureuse et hybride des identités et des accès. L'application garantit une transition fluide et sécurisée entre la première installation, l'enregistrement des utilisateurs locaux, l'intégration des annuaires d'entreprise LDAP et le provisionnement automatique lors de la connexion.

Les finalités majeures sont :
- Garantir qu'une instance sans utilisateur bascule automatiquement vers une phase de configuration initiale (Setup) pour définir le compte superadministrateur (`root`).
- Protéger l'ensemble des fonctionnalités du Dashboard au sein d'une zone strictement authentifiée par jetons JWT.
- Permettre l'enregistrement autonome de nouveaux utilisateurs locaux (`LocalUser`).
- Assurer une authentification hybride intelligente au login :
  - Si l'utilisateur est présent dans la base locale (compte local), validation par mot de passe hashé.
  - Si l'utilisateur est présent dans le LDAP et que son mot de passe est vérifié avec succès par bind LDAP, insérer automatiquement le compte en base de données (`LdapUser` via Just-In-Time Provisioning) et lui délivrer son jeton JWT.
- Offrir la possibilité d'importer et synchroniser des utilisateurs LDAP en masse depuis les paramètres de l'application.
- Conserver une structure de données unifiée et performante en séparant nettement les deux types d'utilisateurs à l'aide d'une table discriminante Doctrine (`Single Table Inheritance`).

### Scope
#### In Scope
- **Détection dynamique de l'état d'installation** : remplacement du mécanisme basé sur fichier par le comptage effectif dans la table `user`.
- **Modèle de données polymorphe** : mise en place de la hiérarchie `User`, `LocalUser` et `LdapUser` en Single Table Inheritance (colonne discriminante `discr`).
- **Workflow de Setup initial** : création contrôlée de l'unique superadministrateur initial avec mot de passe fort et assignation du rôle `ROLE_SUPER_ADMIN`.
- **Authentification hybride & Auto-provisioning (JIT)** :
  - Vérification locale pour `LocalUser`.
  - Vérification LDAP et insertion en base de données pour tout nouvel utilisateur LDAP avec mot de passe valide.
  - Vérification LDAP en direct pour les `LdapUser` déjà présents en base.
- **Authentification & session frontend** : connexion via `/api/login_check`, gestion du token JWT dans Nuxt, garde de navigation sur toutes les pages Dashboard, et affichage dynamique de l'utilisateur connecté dans le layout.
- **Inscription locale** : page `/register` et endpoint sécurisé pour la création de comptes locaux `ROLE_USER`.
- **Synchronisation LDAP dans les Paramètres** : déclenchement synchrone d'importation depuis l'interface de gestion avec retour direct sur le nombre de comptes créés et mis à jour.
- **Visualisation distincte dans l'interface** : affichage du statut de provenance (Local ou LDAP) dans la liste et les détails des utilisateurs.

#### Out of Scope
- Authentification SSO directe via protocoles tiers (SAML / OAuth2 / OIDC).
- Réinitialisation de mot de passe par email sortant (pourra faire l'objet d'une phase ultérieure).

### User Stories
- **En tant que premier opérateur déployant la plateforme**, je suis automatiquement redirigé vers l'écran de configuration pour créer le compte superadministrateur (`root`), afin de poser les bases de la sécurité du système sans configuration manuelle en base.
- **En tant qu'utilisateur local**, je peux m'authentifier avec mes identifiants locaux pour accéder au dashboard.
- **En tant qu'utilisateur de l'annuaire d'entreprise (LDAP)**, lorsque je me connecte pour la première fois avec mes identifiants d'entreprise valides, mon compte est automatiquement créé dans l'application et ma session démarre immédiatement sans intervention d'un administrateur.
- **En tant qu'utilisateur non authentifié**, si j'essaie d'accéder au tableau de bord ou aux entités de gestion, je suis redirigé vers l'écran de connexion afin de protéger les données confidentielles.
- **En tant que nouveau collaborateur local**, je peux m'inscrire depuis le formulaire de création de compte afin d'obtenir un accès standard avec mon adresse email et mon mot de passe.
- **En tant qu'administrateur**, je peux accéder aux paramètres de l'application pour tester la connexion LDAP et lancer une synchronisation manuelle en masse des utilisateurs.
- **En tant qu'administrateur**, je peux visualiser dans la gestion des utilisateurs la provenance de chaque compte (Local ou LDAP) pour une gouvernance claire des accès.

### Functional Requirements
- **Redirection automatique vers Setup** : si la table `user` contient 0 enregistrement, toute navigation web ou appel API non exempté redirige vers `/setup`.
- **Verrouillage du Setup** : dès lors qu'un premier utilisateur existe, l'accès à `/setup` et l'endpoint `POST /api/setup` sont définitivement interdits (HTTP 403 / redirection vers `/login`).
- **Authentification hybride à la connexion (`/api/login_check`)** :
  1. Si un compte existe en base locale sous forme `LocalUser`, validation par le hash local.
  2. Si un compte existe en base locale sous forme `LdapUser`, validation par tentative de bind LDAP.
  3. Si aucun compte n'existe en base locale, recherche dans le LDAP avec le `baseDn` : si l'utilisateur est trouvé et que le mot de passe est validé par bind, création instantanée d'un `LdapUser` en base de données avec `ROLE_USER`, puis génération du JWT.
  4. Si les identifiants sont erronés dans les deux sources, renvoi d'une erreur 401 unifiée ("Identifiants invalides").
- **Garde de sécurité Nuxt** : les routes utilisant le layout `dashboard` (`/dashboard`, `/projects`, `/organisations`, `/integrations`, `/users`, `/settings`) exigent un jeton JWT valide. Si absent ou expiré, redirection vers `/login` avec conservation de l'URL cible.
- **Transmission du Bearer Token** : toute requête cliente vers l'API Platform doit contenir l'en-tête `Authorization: Bearer <token>`.
- **Inscription locale** : formulaire public avec validation d'email, complexité de mot de passe et confirmation.
- **Synchronisation LDAP paramétrable** : bouton d'action dans les paramètres déclenchant un appel API synchrone, créant ou actualisant les entités `LdapUser`.

# Technical Design

### Current Implementation
L'application repose sur une API Symfony / API Platform et un frontend Nuxt 4 avec Nuxt UI v4 :
- **API Setup actuelle** : `SetupController` vérifie l'existence physique du fichier `config/install.lock`. Cette approche est fragile en conteneur éphémère et ne reflète pas l'état réel de la base de données.
- **Sécurité API** : Le bundle `LexikJWTAuthenticationBundle` est configuré sur `/api/login_check` avec le provider `app_user_provider` pointant sur `App\Entity\User`.
- **Frontend** :
  - `front/app/pages/login.vue` contient une maquette statique sans liaison API.
  - `front/app/layouts/dashboard.vue` contient des données d'utilisateur mockées ("Admin") et aucun système de déconnexion fonctionnel.
  - `front/app/middleware/setup.global.ts` interroge `/api/install-status` mais dépend du fichier de lock.
- **LDAP existant** :
  - L'entité `App\Entity\LdapConfiguration` est déjà définie avec host, port, baseDn, bindDn et un endpoint de test `/ldap_configurations/test`.
  - La commande `App\Command\SyncLdapUsersCommand` fournit une première ébauche d'interrogation de l'annuaire mais manipule directement l'entité unique `User`.

### Key Decisions
- **Modèle d'héritage en base : Single Table Inheritance (STI)** :
  - *Choix* : Utiliser une seule table physique `user` avec une colonne discriminante `discr` gérée par Doctrine ORM.
  - *Bénéfice* : Évite les jointures coûteuses tout en garantissant l'intégrité référentielle, l'unicité globale des emails et la compatibilité naturelle avec `lexik_jwt_authentication` et `app_user_provider`.
- **Authentification hybride & Provisionnement Just-In-Time (JIT)** :
  - *Choix* : Implémenter un authentificateur personnalisé `HybridJsonLoginAuthenticator` interceptant `/api/login_check`. Si l'utilisateur n'est pas en base locale mais s'authentifie avec succès sur le serveur LDAP, il est immédiatement instancié en `LdapUser` et persisté en base de données avant la génération du token JWT.
  - *Bénéfice* : Élimine la friction pour les nouveaux utilisateurs de l'annuaire d'entreprise qui peuvent se connecter sans attendre une synchronisation manuelle d'un administrateur, tout en garantissant que seuls les utilisateurs aux identifiants vérifiés sont enregistrés.
- **Ressource API Platform unifiée avec opérations spécialisées** :
  - *Choix* : Exposer `/api/users` pour la consultation et l'administration globale, complété par une opération `POST /api/register` pour l'inscription locale et `POST /api/ldap/sync` pour la synchronisation en masse.
  - *Bénéfice* : Fournit une interface utilisateur consolidée tout en encapsulant les règles métier propres à chaque type d'utilisateur.
- **Synchronisation LDAP synchrone directe** :
  - *Choix* : Déclenchement via un endpoint contrôleur dédié `POST /api/ldap/sync` renvoyant un rapport immédiat au frontend.
  - *Bénéfice* : Offre une rétroaction instantanée sans exiger la mise en place d'une infrastructure de workers asynchrones plus lourde à maintenir.
- **Gestion de session par Cookie sécurisé et Pinia** :
  - *Choix* : Stocker le jeton JWT dans un cookie sécurisé (compatible SSR/CSR) couplé à un store Pinia réactif `useAuthStore`.
  - *Bénéfice* : Permet une vérification transparente dans les middlewares globaux Nuxt dès le premier rendu serveur.

### Architecture Diagram
```mermaid
graph TD
  subgraph Frontend Nuxt 4
    UI_Login[Page /login]
    UI_Setup[Page /setup]
    UI_Register[Page /register]
    UI_Dash[Layout & Pages Dashboard]
    UI_Settings[Page /settings/ldap]
    AuthStore[Pinia AuthStore + Cookie JWT]
    SetupMW[Middleware setup.global.ts]
    AuthMW[Middleware auth.global.ts]
  end

  subgraph API Symfony - Authentification & Contrôleurs
    HybridAuth[HybridJsonLoginAuthenticator]
    SetupCtrl[SetupController]
    RegisterCtrl[RegisterController]
    LdapSyncCtrl[LdapSyncController]
    LdapService[LdapSyncService]
    JWTSuccess[Lexik JWT Success Handler]
  end

  subgraph Base de données PostgreSQL
    TableUser[Table user - STI avec discr local/ldap]
    TableLdapConf[Table ldap_configuration]
  end

  subgraph Annuaire LDAP
    ServerLDAP[Serveur LDAP / Active Directory]
  end

  UI_Login -->|POST /api/login_check| HybridAuth
  HybridAuth -->|1. Cherche dans base| TableUser
  HybridAuth -->|2. Si LocalUser| TableUser
  HybridAuth -->|3. Si LdapUser ou Inconnu -> Bind| ServerLDAP
  ServerLDAP -->|Mot de passe OK| HybridAuth
  HybridAuth -->|4. Si nouvel utilisateur -> Insère LdapUser| TableUser
  HybridAuth -->|5. Succès| JWTSuccess
  JWTSuccess -->|Token JWT| AuthStore

  SetupMW -->|GET /api/install-status| SetupCtrl
  SetupCtrl -->|Count == 0 ?| TableUser
  UI_Setup -->|POST /api/setup| SetupCtrl
  SetupCtrl -->|Insert root LocalUser| TableUser

  UI_Register -->|POST /api/register| RegisterCtrl
  RegisterCtrl -->|Hash & Save LocalUser| TableUser

  AuthMW -->|Vérifie JWT| AuthStore
  UI_Dash -->|Requêtes avec Bearer Token| TableUser

  UI_Settings -->|POST /api/ldap/sync| LdapSyncCtrl
  LdapSyncCtrl --> LdapService
  LdapService -->|Query Entries| ServerLDAP
  LdapService -->|Persist LdapUsers en lot| TableUser
```

### Data Models / Contracts

#### 1. Entités Doctrine
```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['local' => LocalUser::class, 'ldap' => LdapUser::class])]
abstract class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    protected ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $username = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    protected array $roles = [];

    #[Groups(['user:read'])]
    public function getType(): string
    {
        return $this instanceof LocalUser ? 'local' : 'ldap';
    }
}
```

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class LocalUser extends User implements PasswordAuthenticatedUserInterface
{
    #[ORM\Column]
    protected ?string $password = null;

    #[Groups(['user:write'])]
    protected ?string $plainPassword = null;
}
```

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class LdapUser extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    protected ?string $ldapUid = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    protected ?string $distinguishedName = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    protected ?\DateTimeImmutable $syncedAt = null;
}
```

#### 2. Flux d'Authentification Hybride & JIT Provisioning
L'authentificateur `HybridJsonLoginAuthenticator` :
1. Extrait `email` et `password` de la charge utile JSON de `/api/login_check`.
2. Interroge `UserRepository::findOneBy(['email' => $email])`.
3. **Cas 1 - LocalUser trouvé** : vérifie le mot de passe via `UserPasswordHasherInterface`.
4. **Cas 2 - LdapUser trouvé** : effectue un bind direct sur le serveur LDAP avec `$user->getDistinguishedName()` et le mot de passe soumis.
5. **Cas 3 - Utilisateur absent de la base locale** :
   - Récupère la configuration LDAP active depuis `LdapConfigurationRepository`.
   - Effectue une recherche LDAP pour trouver l'entrée correspondant à l'email ou à l'identifiant.
   - Si l'entrée LDAP existe, tente un bind avec son DN et le mot de passe fourni.
   - En cas de bind réussi : instancie un nouvel `LdapUser` avec les attributs LDAP extraits (email, nom d'utilisateur, UID, DN), lui attribue le rôle `ROLE_USER`, et persiste l'entité en base de données.
   - Transmet cette nouvelle instance à l'émetteur de jeton.
6. En cas d'échec à toutes les étapes : déclenche l'échec d'authentification (HTTP 401).

### Components & File Structure
- **Backend (`api/src/`)** :
  - `Entity/User.php` (classe de base abstraite STI)
  - `Entity/LocalUser.php` (classe dérivée locale)
  - `Entity/LdapUser.php` (classe dérivée synchronisée)
  - `Repository/UserRepository.php` (méthodes d'inspection et filtrage par type)
  - `Security/HybridJsonLoginAuthenticator.php` (gestionnaire unifié du login local, LDAP et JIT)
  - `Controller/SetupController.php` (contrôle dynamique par le comptage d'utilisateurs)
  - `Controller/RegisterController.php` (endpoint d'inscription locale)
  - `Controller/LdapSyncController.php` (déclenchement synchrone manuel)
  - `Service/LdapSyncService.php` (extraction LDAP, validation bind et synchronisation Doctrine)
  - `EventListener/InstallListener.php` (interception de requêtes et redirection si table vide)
- **Frontend (`front/app/`)** :
  - `middleware/setup.global.ts` (contrôle d'installation et routage vers `/setup`)
  - `middleware/auth.global.ts` (garde d'authentification pour la zone Dashboard)
  - `stores/auth.ts` (store Pinia de session utilisateur et token JWT)
  - `composables/api.ts` (injection du Bearer token dans les requêtes `$fetch` et `useFetch`)
  - `pages/login.vue` (formulaire de connexion branché à `/api/login_check`)
  - `pages/register.vue` (formulaire de création de compte local)
  - `pages/setup.vue` (formulaire de configuration du superadministrateur initial)
  - `pages/settings/ldap.vue` (configuration, test et bouton de synchronisation LDAP)
  - `layouts/dashboard.vue` (barre latérale avec utilisateur connecté, lien paramètres et déconnexion)
  - `pages/users/index.vue` (liste avec distinction visuelle Local / LDAP)

### Risks & Mitigations
- **Risque de conflit d'email entre un utilisateur Local et un utilisateur LDAP** : un utilisateur LDAP tente de se connecter avec un email déjà attribué à un compte `LocalUser`.  
  *Mitigation* : L'authentificateur vérifie la présence locale en priorité. Si un `LocalUser` existe déjà avec cet email, il ne tente pas de le remplacer et applique la vérification de mot de passe local.
- **Latence d'authentification si le serveur LDAP est lent ou injoignable** :  
  *Mitigation* : Configurer un timeout réseau court (ex: 3 à 5 secondes) sur la connexion LDAP pour éviter de paralyser les requêtes d'authentification lorsque le serveur distant est en panne.
- **Risque d'injection d'utilisateurs non désirés via LDAP** :  
  *Mitigation* : Le filtre de recherche LDAP dans `LdapConfiguration` permet de restreindre l'éligibilité (ex: `(&(objectClass=person)(memberOf=cn=ProjectManagers,...))`).

# Testing

### Validation Approach
La validation sera effectuée de bout en bout en simulant l'ensemble du cycle de vie de l'application : initialisation à vide, verrouillage du setup, inscription locale, connexion sécurisée, synchronisation en masse et auto-provisionnement lors de la connexion LDAP.

### Key Scenarios
- **Scénario 1 : Détection initiale et exécution du Setup**
  1. Partir d'une table `user` vierge (`count === 0`).
  2. Tenter d'accéder à `/` ou `/dashboard` -> Vérifier la redirection automatique vers `/setup`.
  3. Remplir le formulaire avec l'adresse du superadministrateur et un mot de passe robuste.
  4. Valider -> Constater la création en base d'un `LocalUser` ayant `ROLE_SUPER_ADMIN`.
  5. Tenter de réaccéder à `/setup` -> Constater l'interdiction d'accès (redirection vers `/login` ou 403).

- **Scénario 2 : Connexion locale et protection du Dashboard**
  1. Tenter d'accéder directement à `/dashboard` sans être connecté -> Vérifier la redirection immédiate vers `/login`.
  2. S'authentifier avec les identifiants locaux créés lors du Setup.
  3. Vérifier la réception du jeton JWT et sa persistance en cookie.
  4. Vérifier l'arrivée sur le Dashboard avec les informations réelles de l'administrateur affichées dans la barre latérale.
  5. Cliquer sur le bouton de déconnexion -> Vérifier la purge du jeton et le retour vers `/login`.

- **Scénario 3 : Connexion avec auto-provisionnement LDAP (Just-In-Time Provisioning)**
  1. Choisir un utilisateur existant dans l'annuaire LDAP qui n'est **pas encore** présent dans la table `user`.
  2. Sur la page `/login`, saisir son email LDAP et son bon mot de passe.
  3. Vérifier que l'API interroge le LDAP, valide le mot de passe via bind et insère une nouvelle ligne dans la table `user` avec `discr = 'ldap'` et ses attributs LDAP.
  4. Vérifier que le token JWT est délivré et que l'utilisateur accède directement au Dashboard.
  5. Vérifier en base et dans l'interface `/users` que le compte apparaît désormais avec le badge `LDAP`.

- **Scénario 4 : Tentative de connexion LDAP avec mot de passe erroné**
  1. Tenter une connexion avec un email LDAP mais un mot de passe incorrect.
  2. Vérifier que l'API renvoie une réponse HTTP 401.
  3. Vérifier qu'**aucun** utilisateur n'a été inséré dans la table `user`.

- **Scénario 5 : Inscription autonome d'un utilisateur local**
  1. Se rendre sur `/register` depuis le lien présent sur `/login`.
  2. Soumettre un formulaire d'inscription valide.
  3. Vérifier la création d'une entrée `LocalUser` avec rôle `ROLE_USER` et mot de passe correctement hashé.
  4. Vérifier la capacité de cet utilisateur à se connecter immédiatement via `/login`.

- **Scénario 6 : Synchronisation LDAP en lot depuis les paramètres**
  1. Se connecter avec le compte Superadmin ou Administrateur.
  2. Accéder à l'interface `/settings/ldap`.
  3. Tester la connectivité LDAP avec les identifiants configurés (`/ldap_configurations/test`).
  4. Cliquer sur le bouton « Lancer la synchronisation ».
  5. Vérifier la réponse API renvoyant le décompte des utilisateurs créés et mis à jour.
  6. Aller sur la page `/users` et constater la présence des utilisateurs créés sous le type `LdapUser`.

### Edge Cases
- **Conflit d'email Local vs LDAP** : si un compte local porte déjà l'email soumis, le mot de passe local prévaut et l'entrée n'est pas convertie en LDAP.
- **Serveur LDAP inaccessible** : l'authentification échoue proprement avec un message 401 sécurisé (sans divulguer d'informations sensibles sur l'infrastructure).
- **Expiration du jeton JWT** : l'intercepteur API détecte le code HTTP 401, purge le cookie de session et redirige l'utilisateur vers `/login`.

### Test Changes
- **Tests unitaires Symfony (`api/tests/`)** :
  - `UserRepositoryTest` : vérification de la méthode `hasAnyUser()` et de l'instanciation discriminante `LocalUser` vs `LdapUser`.
  - `HybridJsonLoginAuthenticatorTest` : simulation des 3 cas (LocalUser existant, LDAP valide avec auto-provisioning, et échec 401).
  - `LdapSyncServiceTest` : simulation du bind LDAP et de la persistance de `LdapUser`.
- **Tests d'intégration d'API** :
  - `SetupControllerTest` : vérification de la transition d'état et du blocage post-setup.
  - `SecurityAuthenticationTest` : validation des flux `/api/login_check` et de la génération du JWT.
- **Tests End-to-End (`e2e/`)** :
  - Scénario Playwright complet : Redirection Setup -> Création Root -> Connexion Dashboard -> Connexion JIT LDAP -> Inscription locale.

# Delivery Steps

###   Step 1: Modélisation STI Doctrine et détection dynamique de setup
La base de données et l'API distinguent rigoureusement les utilisateurs locaux et LDAP via Single Table Inheritance (STI), et le statut d'installation dépend de la vacuité de la table `user`.

- Refactoriser `App\Entity\User` en entité abstraite ou classe de base avec `#[ORM\InheritanceType('SINGLE_TABLE')]`, `#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]` et `#[ORM\DiscriminatorMap(['local' => LocalUser::class, 'ldap' => LdapUser::class])]`.
- Créer l'entité `App\Entity\LocalUser` pour encapsuler le mot de passe hashé, la gestion du mot de passe en clair (`plainPassword`) et le processeur de hachage `UserPasswordHasherProcessor`.
- Créer l'entité `App\Entity\LdapUser` pour stocker les attributs spécifiques à l'annuaire (`ldapUid`, `distinguishedName`, `syncedAt`) sans stockage de mot de passe local.
- Adapter `App\Repository\UserRepository` pour ajouter la méthode `hasAnyUser(): bool` ou `countUsers(): int`.
- Mettre à jour `App\Controller\SetupController::status()` et `App\EventListener\InstallListener` pour interroger l'existence d'utilisateurs en base plutôt que la seule présence de `install.lock`.
- Mettre à jour `App\Controller\SetupController::setup()` pour créer le premier utilisateur Superadmin (`root`) sous forme d'une instance `LocalUser` avec le rôle `ROLE_SUPER_ADMIN` et bloquer toute exécution ultérieure.
- Générer et appliquer la migration Doctrine correspondante pour la nouvelle colonne discriminante.

###   Step 2: Authentification hybride avec JIT LDAP, garde Dashboard et inscription locale
Le flux de connexion gère à la fois les utilisateurs locaux, les utilisateurs LDAP existants et l'auto-provisionnement en base des nouveaux utilisateurs LDAP valides, avec protection totale du Dashboard.

- Implémenter un authentificateur hybride `App\Security\HybridJsonLoginAuthenticator` pour `/api/login_check` :
  - Si l'utilisateur est trouvé en base locale sous forme `LocalUser`, valider via le hasher de mot de passe Symfony standard.
  - Si l'utilisateur est trouvé en base locale sous forme `LdapUser`, valider ses identifiants en temps réel via un bind sur l'annuaire LDAP.
  - Si l'utilisateur est absent de la base locale, interroger l'annuaire LDAP via les paramètres configurés : si l'entrée existe et que le mot de passe est valide via bind LDAP, insérer immédiatement l'utilisateur en base sous forme d'une entité `LdapUser` (JIT Provisioning) avant de délivrer le jeton JWT.
- Configurer le contrôleur/action d'inscription locale `App\Controller\RegisterController` ou opération API Platform sur `LocalUser` sécurisant la création autonome de compte avec `ROLE_USER`.
- Créer le store Pinia d'authentification `front/app/stores/auth.ts` gérant le token JWT stocké via `useCookie('jwt_token')`, l'état de l'utilisateur courant et les actions `login`, `logout` et `fetchCurrentUser`.
- Mettre en place le middleware global de sécurité frontend `front/app/middleware/auth.global.ts` redirigeant tout utilisateur non connecté tentant d'accéder aux routes sous layout `dashboard` vers `/login`.
- Adapter `front/app/composables/api.ts` pour injecter automatiquement l'en-tête HTTP `Authorization: Bearer <token>` sur l'ensemble des requêtes sécurisées.
- Connecter le formulaire `front/app/pages/login.vue` à `/api/login_check` avec gestion des erreurs et redirection vers le dashboard.
- Créer la page `front/app/pages/register.vue` avec validation Nuxt UI / Zod permettant l'enregistrement d'un utilisateur local.
- Mettre à jour `front/app/layouts/dashboard.vue` pour afficher l'identité réelle de l'utilisateur connecté dans la barre latérale et activer le bouton de déconnexion.

###   Step 3: Synchronisation LDAP synchrone et interface de paramètres
Les administrateurs peuvent configurer et synchroniser manuellement ou en lot les comptes utilisateurs LDAP depuis l'espace de paramètres avec un compte-rendu immédiat.

- Créer le service métier `App\Service\LdapSyncService` exploitant l'entité existante `LdapConfiguration` pour se connecter au serveur LDAP, interroger l'annuaire, vérifier les mots de passe et persister/mettre à jour les instances de `LdapUser`.
- Créer le contrôleur `App\Controller\LdapSyncController` exposant l'opération `POST /api/ldap_configurations/sync` ou `POST /api/ldap/sync` accessible aux administrateurs (`ROLE_ADMIN`) et renvoyant le compte-rendu d'exécution (comptes créés, mis à jour, ignorés).
- Créer la page de paramètres d'annuaire `front/app/pages/settings/ldap.vue` (ou onglet Paramètres) permettant de tester la liaison LDAP et de déclencher la synchronisation synchrone directe.
- Intégrer l'accès aux paramètres dans le menu latéral `front/app/layouts/dashboard.vue`.
- Adapter la vue `front/app/pages/users/index.vue` pour afficher clairement le badge de provenance (Local vs LDAP) et fournir un bouton d'action rapide de synchronisation pour les administrateurs.