---
sessionId: session-260922-093528-v1jj
---

# Requirements

### Overview & Goals
Le projet nécessite la gestion centralisée des serveurs proxies HTTP/HTTPS utilisés pour communiquer avec les outils externes (Jenkins, Gitea, SonarQube, Mantis, Nexus). Actuellement, la configuration du proxy se fait de manière dispersée par des variables d'environnement ou via un champ JSON brut (`options`) dans la configuration des serveurs.

L'objectif de cette évolution est de :
1. Fournir un module CRUD complet (backend API Platform + frontend Nuxt 4 / Nuxt UI v4) pour gérer les configurations de proxies (nom, URL, authentification optionnelle, liste d'exclusions `noProxy`, statut actif).
2. Ajouter dans l'interface utilisateur une liste déroulante (`<USelect>`) permettant de choisir facilement un proxy existant lors de la configuration d'un serveur ou d'une intégration.
3. Centraliser la logique de résolution de proxy dans un service Symfony `ProxyResolver` partagé par l'ensemble des connecteurs (`JenkinsConnector`, `GiteaConnector`, `SonarQubeConnector`, `MantisConnector`, `NexusConnector`).

### Scope
- **In Scope :**
  - Entité Doctrine `Proxy` exposée via API Platform avec validation et filtres.
  - Relations ManyToOne optionnelles vers `Proxy` sur `Server` (proxy par défaut pour l'hôte) et sur `Integration` (surcharge possible par connecteur).
  - Service backend `App\Service\ProxyResolver` unifiant la gestion des proxies, du bypass `noProxy`, du mode direct et des fallbacks système.
  - Adaptation des 5 connecteurs existants (`Jenkins`, `Gitea`, `SonarQube`, `Mantis`, `Nexus`) pour déléguer la configuration proxy au `ProxyResolver`.
  - Génération des stores Pinia, types TypeScript et composants Nuxt UI v4 via `NuxtUi4Generator` (`pnpm client-generator -r Proxy`).
  - Page de gestion des proxies (`front/app/pages/proxies/index.vue` et `[id]/index.vue`) et entrée dans le menu `front/app/layouts/dashboard.vue`.
  - Liste déroulante des proxies dans `ServerForm.vue` et `IntegrationForm.vue`.
  - Tests unitaires et d'intégration (PHPUnit) et validation frontend.
- **Out of Scope :**
  - Serveurs de déploiement SSH (`DeploymentServer`) qui n'utilisent pas de proxy HTTP standard.
  - Proxy SOCKS5 avancé ou rotation dynamique de proxies.

### User Stories
- **En tant qu'administrateur**, je souhaite créer, lister, modifier et désactiver des configurations de proxies réseau depuis une interface dédiée, afin de centraliser les règles d'accès de l'infrastructure.
- **En tant que responsable de projet**, je souhaite sélectionner un proxy préconfiguré via une liste déroulante lors de l'ajout d'un serveur ou d'une intégration, afin d'éviter la saisie manuelle de chaînes JSON ou d'URLs sensibles.
- **En tant que développeur**, je souhaite que les requêtes des connecteurs passent automatiquement par le proxy configuré (ou le contournent pour les domaines internes) lors des tests de santé et de synchronisation des données.

### Functional Requirements
- **CRUD Proxy :**
  - Champs requis : Nom du proxy (ex: "Proxy GDB Entreprise"), URL du proxy (ex: `http://px.groupegdb.local:8080`).
  - Champs optionnels : Nom d'utilisateur, Mot de passe (pour proxies authentifiés), Domaines d'exclusion `noProxy` (séparés par des virgules), Statut actif (`enabled`, booléen par défaut `true`).
  - Opérations supportées : Création, Consultation détaillée, Modification, Suppression, et Listing paginé avec recherche textuelle.
- **Sélection de Proxy dans les Serveurs (`ServerForm.vue`) :**
  - Liste déroulante proposant : "Proxy par défaut du système (HTTP_PROXY)", "Connexion directe (sans proxy)", et la liste des proxies configurés actifs.
- **Sélection de Proxy dans les Intégrations (`IntegrationForm.vue`) :**
  - Liste déroulante proposant : "Hériter du serveur associé (Recommandé)", "Connexion directe (sans proxy)", ou choix explicite d'un proxy configuré.
- **Résolution dans les Connecteurs :**
  - Priorité de résolution :
    1. Proxy explicitement sélectionné sur l'intégration (`$integration->getProxy()`).
    2. Proxy sélectionné sur le serveur associé (`$server->getProxy()`).
    3. Option legacy JSON `$server->getOptions()['proxy']` (rétrocompatibilité).
    4. Fallback vers le proxy système de l'environnement (`HTTP_PROXY`) si le domaine cible ne figure pas dans `NO_PROXY` ni dans la liste d'exclusion du proxy.

### Non-Functional Requirements
- **Compatibilité Nuxt UI v4 :** Utilisation des composants officiels `UFormField`, `USelect`, `UInput`, `UCard`, `UModal`, `UBadge`.
- **Typage strict PHP 8.4 :** `declare(strict_types=1);`, types complets sur tous les paramètres et retours de méthodes.
- **Sécurité :** Ne pas exposer les mots de passe de proxies en clair dans les logs ou les messages d'erreur.

# Technical Design

### Current Implementation
- **Gestion actuelle du proxy :**
  - Les connecteurs (`JenkinsConnector`, `GiteaConnector`, `MantisConnector`, `NexusConnector`, `SonarQubeConnector`) lisent `$options['proxy']` depuis le champ JSON `$server->getOptions()`, ou se rabattent sur la variable `$_SERVER['HTTP_PROXY']`.
  - Seul `JenkinsConnector` dispose actuellement d'une méthode `resolveProxy()` tenant compte des domaines internes et de `NO_PROXY`. Les 4 autres connecteurs dupliquent du code élémentaire.
  - Dans l'interface frontend (`ServerForm.vue`), les options réseau sont saisies dans un `UTextarea` sous forme de JSON brut. `IntegrationForm.vue` ne propose aucun réglage de proxy.

### Key Decisions
1. **Modèle de données : Entité `Proxy` dédiée avec relations ManyToOne :**
   - *Choix :* Créer une entité Doctrine `Proxy` de premier niveau et ajouter une relation ManyToOne optionnelle sur `Server` et sur `Integration`.
   - *Rationale :* Permet de définir un proxy par défaut pour l'hôte réseau (`Server`) tout en offrant la flexibilité de surcharger ponctuellement pour un connecteur précis (`Integration`).
2. **Centralisation de la logique dans un service `ProxyResolver` :**
   - *Choix :* Déplacer toute la logique d'extraction, de bypass d'hôte interne et de respect de `NO_PROXY` dans `App\Service\ProxyResolver`.
   - *Rationale :* Élimine la duplication de code entre les 5 connecteurs et garantit un comportement réseau homogène dans toute l'application.
3. **Génération Frontend via `NuxtUi4Generator` :**
   - *Choix :* Utiliser le générateur sur mesure du projet (`docker compose exec front pnpm client-generator -r Proxy`) pour créer les types, stores Pinia et composants de base, puis intégrer la page dans le dashboard Nuxt 4.
   - *Rationale :* Respecte fidèlement les conventions du projet et l'arborescence Nuxt UI v4 (`front/app/`).

### Architecture Diagram
```mermaid
graph TD
    UI_Page[Page Proxies /proxies] --> ProxyStore[Pinia ProxyStore]
    ProxyStore --> ApiPlatform[API Platform /api/proxies]
    ApiPlatform --> ProxyEntity[Entité Proxy]
    
    ServerForm[ServerForm.vue] -. Sélection Proxy .-> ProxyEntity
    IntegrationForm[IntegrationForm.vue] -. Surcharge Proxy .-> ProxyEntity
    
    ProxyEntity --> ServerEntity[Entité Server]
    ProxyEntity --> IntegrationEntity[Entité Integration]
    
    IntegrationEntity --> ProxyResolver[Service ProxyResolver]
    ServerEntity --> ProxyResolver
    
    ProxyResolver --> JenkinsConn[JenkinsConnector]
    ProxyResolver --> GiteaConn[GiteaConnector]
    ProxyResolver --> SonarConn[SonarQubeConnector]
    ProxyResolver --> MantisConn[MantisConnector]
    ProxyResolver --> NexusConn[NexusConnector]
    
    JenkinsConn --> HttpClient[Symfony HttpClient]
    GiteaConn --> HttpClient
    SonarConn --> HttpClient
    MantisConn --> HttpClient
    NexusConn --> HttpClient
```

### Data Models / Contracts

#### Entité `App\Entity\Proxy`
```php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProxyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['proxy:read']],
    denormalizationContext: ['groups' => ['proxy:write']]
)]
class Proxy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['proxy:read', 'server:read', 'integration:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['proxy:read', 'proxy:write', 'server:read', 'integration:read'])]
    private string $name;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['proxy:read', 'proxy:write', 'server:read', 'integration:read'])]
    private string $url;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['proxy:read', 'proxy:write'])]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['proxy:write'])]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['proxy:read', 'proxy:write'])]
    private ?string $noProxy = null;

    #[ORM\Column]
    #[Groups(['proxy:read', 'proxy:write', 'server:read', 'integration:read'])]
    private bool $enabled = true;

    #[ORM\Column]
    #[Groups(['proxy:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['proxy:read'])]
    private ?\DateTimeImmutable $updatedAt = null;
    
    // Getters & Setters...
}
```

#### Service `App\Service\ProxyResolver`
```php
namespace App\Service;

use App\Entity\Integration;
use App\Entity\Server;
use App\Entity\Proxy;

class ProxyResolver
{
    /**
     * Résout les options de proxy pour Symfony HttpClient.
     * @return array{proxy?: string}
     */
    public function resolveRequestOptions(Integration $integration): array
    {
        $targetHost = (string) ($integration->getServer()?->getHost() ?? '');
        $proxy = $this->determineProxy($integration);
        
        // Règle 1 : Proxy explicite désactivé / mode direct
        if ($proxy === 'direct' || $proxy === 'none') {
            return ['proxy' => ''];
        }
        
        // Règle 2 : Proxy configuré
        if ($proxy instanceof Proxy) {
            if (!$proxy->isEnabled()) {
                return ['proxy' => ''];
            }
            if ($this->isHostExcluded($targetHost, $proxy->getNoProxy())) {
                return ['proxy' => ''];
            }
            return ['proxy' => $this->buildProxyUrl($proxy)];
        }
        
        // Règle 3 : Fallback legacy ou environnement système
        return $this->resolveSystemFallback($targetHost);
    }
}
```

### Components
- **Backend :**
  - `App\Entity\Proxy` : Entité principale.
  - `App\Repository\ProxyRepository` : Repository Doctrine.
  - `App\Service\ProxyResolver` : Service unifié de résolution de proxy pour HttpClient.
  - Connecteurs modifiés : `JenkinsConnector`, `GiteaConnector`, `SonarQubeConnector`, `MantisConnector`, `NexusConnector`.
  - Entités modifiées : `Server` (ajout `$proxy`), `Integration` (ajout `$proxy`).
- **Frontend :**
  - `front/app/components/proxy/` : Composants générés `ProxyCreate`, `ProxyUpdate`, `ProxyShow`, `ProxyList`, `ProxyForm`.
  - `front/app/stores/proxy/` : Stores Pinia CRUD.
  - `front/app/types/proxy.ts` : Interface TypeScript.
  - `front/app/pages/proxies/index.vue` : Page de gestion avec liste, cartes et modales.
  - `front/app/components/server/ServerForm.vue` : Intégration de la liste déroulante `<USelect>` de proxies.
  - `front/app/components/integration/IntegrationForm.vue` : Intégration de la liste déroulante de surcharge de proxy.
  - `front/app/layouts/dashboard.vue` : Entrée de navigation "Proxies".

### File Structure
```
api/
  src/
    Entity/
      Proxy.php
      Server.php (modifié)
      Integration.php (modifié)
    Repository/
      ProxyRepository.php
    Service/
      ProxyResolver.php
    Integration/
      Connector/
        JenkinsConnector.php (modifié)
        GiteaConnector.php (modifié)
        SonarQubeConnector.php (modifié)
        MantisConnector.php (modifié)
        NexusConnector.php (modifié)
    Migrations/
      VersionXXXXXXXXXXXXXX.php
  tests/
    Api/
      ProxyApiTest.php
    Unit/
      ProxyResolverTest.php
front/
  app/
    components/
      proxy/
        ProxyCreate.vue
        ProxyUpdate.vue
        ProxyShow.vue
        ProxyList.vue
        ProxyForm.vue
      server/
        ServerForm.vue (modifié)
      integration/
        IntegrationForm.vue (modifié)
    pages/
      proxies/
        index.vue
        [id]/index.vue
    stores/
      proxy/
    types/
      proxy.ts
    layouts/
      dashboard.vue (modifié)
```

### Risks & Mitigations
- **Risque d'écrasement des proxies déjà configurés dans `options` :**
  - *Mitigation :* `ProxyResolver` maintient la compatibilité descendante en lisant `$server->getOptions()['proxy']` si aucun objet `Proxy` n'est rattaché.
- **Identifiants de proxy dans l'URL :**
  - *Mitigation :* Encodage soigné des identifiants (`urlencode`) dans le format d'URL `http://user:pass@host:port` ou utilisation de l'option CURL appropriée sans loguer les secrets.

# Testing

### Validation Approach
La validation sera effectuée à l'aide de tests automatisés PHPUnit (tests d'API Platform et tests unitaires du service de résolution) ainsi que par la validation du build TypeScript et des tests de connectivité en direct.

### Key Scenarios
1. **CRUD Proxy via API :**
   - Créer un proxy via POST `/api/proxies` avec nom, URL, exclusions `noProxy`.
   - Modifier le proxy via PATCH `/api/proxies/{id}`.
   - Lister les proxies via GET `/api/proxies` avec contrôle de la sérialisation (le mot de passe ne doit pas fuiter en lecture).
   - Supprimer le proxy via DELETE `/api/proxies/{id}` et vérifier que les serveurs et intégrations associés voient leur champ proxy passer à `null` (`SET NULL`).
2. **Priorité de résolution du proxy :**
   - Serveur sans proxy -> fallback vers le comportement par défaut / système.
   - Serveur avec proxy configuré -> les requêtes du connecteur transitent par ce proxy.
   - Intégration avec proxy surchargé -> la requête du connecteur utilise le proxy spécifique de l'intégration au lieu de celui du serveur.
   - Intégration configurée en "Direct" -> aucune utilisation de proxy même si le serveur ou l'environnement en définissent un.
3. **Contournement des domaines internes (`noProxy`) :**
   - Vérifier qu'un hôte présent dans la liste `noProxy` du proxy sélectionné (ou dans `NO_PROXY` système) désactive automatiquement le proxy pour cette requête.
4. **Validation de tous les connecteurs :**
   - Exécuter la suite de tests pour Jenkins, Gitea, SonarQube, Mantis et Nexus avec injection de `ProxyResolver`.

### Edge Cases
- Proxy désactivé (`enabled = false`) : le résolveur ne doit pas l'utiliser et se comporter en connexion directe ou lever un avertissement clair.
- URL de proxy malformée : validation `@Assert\Url` dans l'entité `Proxy` pour bloquer la saisie d'URLs invalides dès l'API.
- Caractères spéciaux dans le mot de passe du proxy : encodage RFC 3986 adéquat lors de l'assemblage de l'URL proxy avec authentification.

### Test Changes
- **Nouveau fichier de test d'API :** `api/tests/Api/ProxyApiTest.php`
- **Nouveau fichier de test unitaire :** `api/tests/Unit/ProxyResolverTest.php`
- **Mise à jour des tests d'intégration :**
  - `api/tests/Integration/JenkinsConnectorTest.php`
  - `api/tests/Integration/GiteaConnectorTest.php`
  - `api/tests/Integration/SonarQubeConnectorTest.php`
  - `api/tests/Integration/MantisConnectorTest.php`
  - `api/tests/Integration/NexusConnectorTest.php`

# Delivery Steps

### ✓ Step 1: Backend Proxy Entity, API Platform Resource, and Database Migration
L'entité `Proxy` est créée avec ses opérations API Platform complètes, ses migrations Doctrine appliquées, et les relations sur `Server` et `Integration` configurées.

- Créer l'entité `App\Entity\Proxy` (`id`, `name`, `url`, `username`, `password`, `noProxy`, `enabled`, `createdAt`, `updatedAt`) avec les annotations API Platform `#[ApiResource]` et groupes de sérialisation (`proxy:read`, `proxy:write`).
- Créer le repository `App\Repository\ProxyRepository`.
- Mettre à jour `App\Entity\Server` pour ajouter la relation `#[ORM\ManyToOne(targetEntity: Proxy::class)]` `$proxy` (avec `server:read`, `server:write`).
- Mettre à jour `App\Entity\Integration` pour ajouter la relation `#[ORM\ManyToOne(targetEntity: Proxy::class)]` `$proxy` (avec `integration:read`, `integration:write`) permettant la surcharge optionnelle par connecteur.
- Générer et exécuter la migration Doctrine (`bin/console make:migration` puis `bin/console doctrine:migrations:migrate`).
- Créer le test d'API `api/tests/Api/ProxyApiTest.php` validant les opérations GET, POST, PUT/PATCH, DELETE et les filtres.

### ✓ Step 2: Centralized ProxyResolver Service and Connectors Refactoring
Un service unique `ProxyResolver` gère la résolution du proxy (priorité Intégration > Serveur > Legacy/Système) et tous les connecteurs l'utilisent.

- Développer le service `App\Service\ProxyResolver` avec la méthode `resolveProxyOptions(Integration $integration): array` gérant la chaîne de priorité (surcharge de l'intégration, proxy du serveur, liste d'exclusions `noProxy`, et fallback environnement).
- Refactoriser `JenkinsConnector` pour injecter `ProxyResolver` et utiliser les options de proxy centralisées lors des appels HTTP (`testConnection`, `checkHealth`, `getLiveData`).
- Refactoriser `GiteaConnector` pour utiliser `ProxyResolver` sur l'ensemble de ses requêtes API.
- Refactoriser `SonarQubeConnector` pour utiliser `ProxyResolver` sur toutes ses requêtes REST.
- Refactoriser `MantisConnector` pour utiliser `ProxyResolver` sur les flux REST et SOAP.
- Refactoriser `NexusConnector` pour utiliser `ProxyResolver` sur la découverte des dépôts et les health checks.
- Écrire les tests unitaires dans `api/tests/Unit/ProxyResolverTest.php` et mettre à jour les tests d'intégration des 5 connecteurs.

### ✓ Step 3: Frontend Proxy CRUD and Management Pages
L'interface d'administration complète des proxies (liste, création, édition, suppression, détails) est accessible dans le dashboard Nuxt.

- Exécuter la génération automatique du client frontend avec `NuxtUi4Generator` via `docker compose exec front pnpm client-generator -r Proxy`.
- Vérifier et ajuster les types TypeScript générés dans `front/app/types/proxy.ts` et les stores Pinia dans `front/app/stores/proxy/`.
- Créer la page de gestion `front/app/pages/proxies/index.vue` avec vue Grille (`UCard`), vue Tableau (`ProxyList`), barre de recherche réactive et modales d'ajout/modification (`ProxyCreate`, `ProxyUpdate`).
- Créer la page de détails `front/app/pages/proxies/[id]/index.vue` basée sur `ProxyShow`.
- Ajouter l'entrée "Proxies" avec l'icône `i-heroicons-globe-alt` dans la navigation latérale de `front/app/layouts/dashboard.vue`.

### ✓ Step 4: Proxy Dropdown Integration in Server and Integration Forms
Les formulaires de création et d'édition de serveurs et d'intégrations proposent une liste déroulante réactive pour sélectionner un proxy configuré.

- Mettre à jour `front/app/components/server/ServerForm.vue` pour remplacer le champ JSON brut par un `<USelect>` listant les proxies configurés (avec options "Aucun proxy / Connexion directe", "Proxy système par défaut", et les proxies enregistrés).
- Mettre à jour `front/app/components/integration/IntegrationForm.vue` pour intégrer un champ `<USelect>` de proxy avec l'option par défaut "Hériter du serveur associé" et la possibilité de surcharger avec un proxy spécifique.
- Adapter `ServerShow.vue` et `IntegrationShow.vue` pour afficher le badge et les détails du proxy actif.
- Valider le bon fonctionnement de bout en bout : création d'un proxy dans l'interface, sélection sur un serveur Jenkins/Gitea/SonarQube/Nexus/Mantis, et exécution réussie du test de connexion.