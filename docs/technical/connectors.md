# Documentation Technique : Connecteurs d'Intégration

Ce document détaille l'architecture, le fonctionnement technique, les protocoles réseau, la gestion des proxies et les procédures d'extension des **connecteurs d'intégration** dans l'application Project Manager.

---

## 1. Vue d'ensemble & Rôle

Project Manager s'interface avec de multiples outils tiers du cycle de vie logiciel (CI/CD, gestion de code source, qualité logicielle, suivi d'anomalies, gestionnaires d'artefacts).

Le système d'intégration repose sur trois niveaux d'abstraction :
1. **`Server`** : Représente la machine hôte ou le service distant (hôte, port, schéma HTTP/HTTPS, type d'authentification et identifiants, configuration proxy par défaut).
2. **`Integration`** : Représente une instance de connecteur associée à un serveur (ex: Jenkins CI, Forge Gitea, Serveur SonarQube, Nexus Repository, Mantis Bug Tracker) avec possibilité de surcharger le proxy réseau.
3. **`ProjectIntegration`** : Associe un projet métier (`Project`) à une intégration avec des paramètres cibles précis (ex: job Jenkins spécifique, dépôt Gitea, clé de projet SonarQube, projet Mantis, dépôt Nexus).

---

## 2. Architecture Globale

```mermaid
graph TD
    subgraph API REST
        CtrlTest[IntegrationTestController<br>/api/integrations/{id}/test]
        CtrlHealth[ProjectIntegrationHealthController<br>/api/project_integrations/{id}/health]
        CtrlLive[ProjectIntegrationLiveDataController<br>/api/project_integrations/{id}/live-data]
        CtrlProjects[IntegrationProjectsController<br>/api/integrations/{id}/projects]
    end

    subgraph Coeur Intégration
        Registry[IntegrationRegistry]
        ProxyRes[ProxyResolver]
        ParamFactory[IntegrationParamFactory]
    end

    subgraph Connecteurs
        Jenkins[JenkinsConnector]
        Gitea[GiteaConnector]
        Sonar[SonarQubeConnector]
        Mantis[MantisConnector]
        Nexus[NexusConnector]
    end

    CtrlTest --> Registry
    CtrlHealth --> Registry
    CtrlLive --> Registry
    CtrlProjects --> Registry

    CtrlHealth --> ParamFactory

    Registry --> Jenkins
    Registry --> Gitea
    Registry --> Sonar
    Registry --> Mantis
    Registry --> Nexus

    Jenkins --> ProxyRes
    Gitea --> ProxyRes
    Sonar --> ProxyRes
    Mantis --> ProxyRes
    Nexus --> ProxyRes

    Jenkins --> HttpClient[Symfony HttpClient]
    Gitea --> HttpClient
    Sonar --> HttpClient
    Mantis --> HttpClient
    Nexus --> HttpClient
```

### Interfaces Fondamentales

#### `IntegrationConnectorInterface`
Localisation : `api/src/Integration/Connector/IntegrationConnectorInterface.php`

Chaque connecteur implémente obligatoirement cette interface :
- `supports(string $type): bool` : Indique si le connecteur prend en charge le type spécifié (ex: `jenkins`, `gitea`, `sonarqube`, `mantis`, `nexus`).
- `getType(): string` : Retourne l'identifiant unique du type.
- `getName(): string` : Retourne le libellé lisible du connecteur.
- `testConnection(Integration $integration): ConnectionTestResult` : Valide la joignabilité et l'authentification de l'intégration globale.
- `checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult` : Vérifie l'état de santé opérationnel d'une ressource ciblée pour un projet.
- `getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array` : Récupère les données en direct (builds, métriques, anomalies, branches, etc.) pour alimenter le dashboard.

#### `ProjectProviderConnectorInterface`
Localisation : `api/src/Integration/Connector/ProjectProviderConnectorInterface.php`

Interface optionnelle implémentée par les connecteurs capables de fournir une liste dynamique de ressources/projets distants :
- `getProjects(Integration $integration): array` : Retourne un tableau d'éléments sous forme `array<int, array{id: string|int, name: string, raw_name?: string}>`.

#### `IntegrationRegistry`
Localisation : `api/src/Integration/IntegrationRegistry.php`

Service de registre centralisant l'ensemble des connecteurs via le mécanisme d'autoconfiguration Symfony (`#[TaggedIterator('app.integration_connector')]`). Il fournit les méthodes :
- `getConnector(string $type): ?IntegrationConnectorInterface`
- `getConnectors(): array<string, IntegrationConnectorInterface>`

#### `ConnectionTestResult` (DTO)
Localisation : `api/src/Integration/Dto/ConnectionTestResult.php`

Objet de transfert standardisant les retours des vérifications réseau :
- `success: bool` : Succès ou échec de l'opération.
- `status: string` : Statut normalisé (`healthy`, `degraded`, `unhealthy`, `unknown`).
- `message: string` : Message descriptif de l'état.
- `testedAt: \DateTimeImmutable` : Horodatage du test.
- `details: array` : Données techniques complémentaires (version du serveur, métadonnées, etc.).

---

## 3. Gestion Réseau & Proxies (`ProxyResolver`)

Localisation : `api/src/Service/ProxyResolver.php`

L'infrastructure réseau nécessite fréquemment de faire transiter les requêtes par des proxys HTTP d'entreprise, tout en contournant ces proxys pour les hôtes locaux ou les domaines internes.

### Ordre de priorité de résolution

Lors de l'appel à `ProxyResolver::resolveProxyOptions(Integration $integration)`, la résolution s'effectue dans l'ordre suivant :

1. **Proxy surchargé au niveau de l'Intégration** (`$integration->getProxy()`) :
   - Si un proxy est explicitement sélectionné sur l'intégration, il est prioritaire.
2. **Proxy défini au niveau du Serveur** (`$server->getProxy()`) :
   - Si aucun proxy n'est configuré sur l'intégration, le proxy du serveur hôte est utilisé.
3. **Option legacy JSON dans `$server->getOptions()['proxy']`** :
   - Assure la rétrocompatibilité avec les anciennes configurations.
4. **Fallback système de l'environnement** :
   - Lecture des variables standard `HTTP_PROXY`, `http_proxy`, `HTTPS_PROXY`, `https_proxy`.

### Règles d'exclusion et Contournement Automatique (Bypass)

Le résolveur désactive automatiquement l'utilisation d'un proxy dans les cas suivants :

- **Mode direct explicite** : Si la configuration spécifie `direct`, `none`, `off`, `""` ou si l'entité `Proxy` a son statut `enabled = false`, le retour est forcé à `['proxy' => '']`.
- **Hôtes internes et locaux** :
  - `localhost`, `127.0.0.1`, `::1`.
  - Noms de domaine se terminant par `.local`.
  - Domaines internes d'entreprise configurés (ex: `bm-energies.com`, `*.bm-energies.com`).
  - Plages d'adresses privées RFC 1918 (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16).
- **Liste `noProxy`** :
  - Respecte la liste d'exclusions définie sur l'entité `Proxy` (séparée par des virgules).
  - Respecte la variable d'environnement `NO_PROXY` / `no_proxy`.

---

## 4. Modèle de Données & Paramétrage Polymorphique

### Authentification Serveur (`ServerAuthenticationType`)
Les serveurs supportent 3 modes d'authentification principaux :
- **`Basic`** : En-tête HTTP Basic Authentication (`Authorization: Basic base64(user:pass)`).
- **`Token`** : En-tête Bearer ou jeton propriétaire (`Authorization: Bearer <token>`, `Authorization: token <token>`, etc.).
- **`Aucune`** : Connexion sans identifiants ni en-têtes d'authentification (accès public ou anonyme, utilisé notamment pour Nexus).

### Paramètres de Projet (`IntegrationParamInterface`)

Chaque type de connecteur dispose d'un modèle de paramètres dédié pour cibler une ressource spécifique au sein d'un projet (`ProjectIntegration`) :

| Connecteur | Classe Entité Paramètre | Champs Obligatoires | Champs Optionnels | Affichage Cible |
| :--- | :--- | :--- | :--- | :--- |
| **Jenkins** | `JenkinsIntegrationParam` | `folder` ou `jobName` | `jobs` (sous-jobs découverts) | `Dossier (N jobs)` ou nom du job |
| **Gitea** | `GiteaIntegrationParam` | `repository` (`owner/repo`) | `branch` | `owner/repo (branche)` |
| **SonarQube** | `SonarQubeIntegrationParam` | `projectKey` | Aucun | Clé de projet |
| **Mantis** | `MantisIntegrationParam` | `projectId` | `projectName` | `NomProjet (#ID)` |
| **Nexus** | `NexusIntegrationParam` | `repository` | `group`, `format` | `dépôt (groupe)` |

La classe utilitaire `IntegrationParamFactory::create(string $type, array $parameters)` instancie automatiquement la classe spécialisée correspondante.

---

## 5. Détail des Connecteurs Implémentés

### 5.1. Connecteur Jenkins (`JenkinsConnector`)
- **Type** : `jenkins`
- **Interfaces** : `IntegrationConnectorInterface`, `ProjectProviderConnectorInterface`
- **Authentification** : `Basic` (nom d'utilisateur + API Token ou mot de passe).
- **Fonctionnement des requêtes** :
  - **`testConnection`** : Requête HTTP GET vers `/api/json`. Vérifie la version de Jenkins (en-tête `X-Jenkins`), le mode du serveur et le nombre de jobs racine.
  - **`checkHealth`** :
    - Si un `jobName` est configuré : GET `/job/{jobName}/api/json`.
    - Si un `folder` est configuré : GET `/job/{folder}/api/json`.
    - Analyse le score de santé (`healthReport`) et le résultat du dernier build (`SUCCESS`, `UNSTABLE`, `FAILURE`).
  - **`getLiveData`** : Récupère les métriques de build en temps réel (numéro du dernier build, statut, durée, horodatage, URL du build, état d'exécution).
  - **`getProjects` / `fetchJobs`** : Explore l'arborescence des dossiers Jenkins (supporte les sous-dossiers `/job/nom-dossier/job/nom-sous-dossier/`) et liste les jobs disponibles.

### 5.2. Connecteur Gitea (`GiteaConnector`)
- **Type** : `gitea`
- **Interfaces** : `IntegrationConnectorInterface`
- **Authentification** : `Token` (en-tête `Authorization: token <token>`) ou `Basic`.
- **Fonctionnement des requêtes** :
  - **`testConnection`** : Appels successifs vers `/api/v1/version` puis `/api/v1/user` pour valider les droits de l'utilisateur.
  - **`checkHealth`** : Vérification du dépôt configuré (`GiteaIntegrationParam`) via GET `/api/v1/repos/{owner}/{repo}`. Vérifie l'accessibilité, les permissions et si le dépôt est archivé.
  - **`getLiveData`** : Récupère les branches actives (`/api/v1/repos/{owner}/{repo}/branches`), les derniers commits (`/commits`) et le nombre de Pull Requests ouvertes (`/pulls?state=open`).

### 5.3. Connecteur SonarQube (`SonarQubeConnector`)
- **Type** : `sonarqube`
- **Interfaces** : `IntegrationConnectorInterface`
- **Authentification** : Token d'analyse SonarQube (transmis via HTTP Basic Auth avec le token comme nom d'utilisateur et un mot de passe vide).
- **Fonctionnement des requêtes** :
  - **`testConnection`** : Vérifie l'état système via `/api/system/status` (ou version `/api/server/version`) et valide les droits via `/api/authentication/validate`.
  - **`checkHealth`** : Contrôle l'état du Quality Gate du projet via `/api/qualitygates/project_status?projectKey={projectKey}` (statut `OK`, `WARN`, ou `ERROR`).
  - **`getLiveData`** : Récupère les mesures d'analyse de code via `/api/measures/component` :
    - Bugs, vulnérabilités de sécurité, code smells.
    - Couverture de tests (`coverage`).
    - Taux de duplication de code (`duplicated_lines_density`).
    - Note de maintenabilité et dette technique.

### 5.4. Connecteur Mantis Bug Tracker (`MantisConnector`)
- **Type** : `mantis`
- **Interfaces** : `IntegrationConnectorInterface`, `ProjectProviderConnectorInterface`
- **Authentification** : API Token MantisBT (en-tête `Authorization: <token>`).
- **Fonctionnement des requêtes** :
  - **`testConnection`** : Requête REST vers `/api/rest/users/me` (avec repli de compatibilité vers SOAP `/api/soap/mantisconnect.php` pour les serveurs anciens).
  - **`checkHealth`** : Contrôle l'accessibilité du projet configuré via GET `/api/rest/projects/{projectId}`. Met à jour le nom du projet dans les paramètres si nécessaire.
  - **`getLiveData`** : Récupère les statistiques d'anomalies via `/api/rest/issues?project_id={projectId}` : décompte total, anomalies ouvertes, résolues, fermées, et répartition par sévérité.
  - **`getProjects`** : Liste l'ensemble des projets Mantis auxquels le compte a accès via `/api/rest/projects/`.

### 5.5. Connecteur Nexus Repository Manager (`NexusConnector`)
- **Type** : `nexus`
- **Interfaces** : `IntegrationConnectorInterface`, `ProjectProviderConnectorInterface`
- **Authentification** : Supporte `Basic`, `Token`, ou `Aucune`.
- **Particularités techniques & Cumul de dépôts** :
  - **Cumul privé & public** : `NexusConnector::fetchRepositories()` effectue deux passes :
    1. Si une authentification est fournie, récupération des dépôts privés avec identifiants.
    2. Si le mode `Aucune` est sélectionné ou si des dépôts publics anonymes existent, récupération complémentaire en mode anonyme, avec fusion et déduplication par nom.
  - **Repli automatique (`requestWithFallback`)** : Si une requête de récupération de composants ou d'actifs échoue en mode authentifié (ex: compte avec droits restreints aux dépôts privés), le connecteur effectue une seconde tentative transparente en mode anonyme.
- **Fonctionnement des requêtes** :
  - **`testConnection`** : Vérifie l'état du service via `/service/rest/v1/status` et dénombre les dépôts accessibles via `fetchRepositories(throwAuthErrors: true)`.
  - **`checkHealth`** : Vérifie la présence du dépôt configuré (`NexusIntegrationParam`) dans la liste des dépôts en ligne.
  - **`getLiveData`** : Récupère les détails du dépôt, le format (`maven2`, `npm`, `raw`, `docker`), le type (`hosted`, `proxy`, `group`), ainsi que les composants (`/service/rest/v1/components`) et actifs (`/service/rest/v1/assets`) récents.
  - **`getProjects`** : Expose la liste des dépôts Nexus sous forme de projets sélectionnables.

---

## 6. Endpoints API REST

| Méthode | Route | Contrôleur | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/integrations/{id}/test` | `IntegrationTestController` | Teste la connectivité d'une intégration existante ou à la volée. Met à jour `status`, `statusMessage` et `lastCheckedAt`. |
| `GET` | `/api/project_integrations/{id}/health` | `ProjectIntegrationHealthController` | Exécute le health check pour une liaison projet-intégration spécifique et persiste le résultat. |
| `GET` | `/api/project_integrations/{id}/live-data` | `ProjectIntegrationLiveDataController` | Récupère les métriques en direct pour l'affichage dans le tableau de bord projet. |
| `GET` | `/api/integrations/{id}/projects` | `IntegrationProjectsController` | Retourne la liste des projets/dépôts disponibles pour les connecteurs implémentant `ProjectProviderConnectorInterface`. |

---

## 7. Guide Développeur : Ajouter un Nouveau Connecteur

Pour intégrer un nouvel outil tiers (ex: GitLab, Jira, Harbor) :

### Étape 1 : Créer la classe de paramétrage de projet
Créez `api/src/Entity/<Nom>IntegrationParam.php` étendant `AbstractIntegrationParam` :
```php
namespace App\Entity;

class GitLabIntegrationParam extends AbstractIntegrationParam
{
    private ?string $projectId = null;

    public function getType(): string { return 'gitlab'; }
    // Getters, Setters, validate(), toArray(), fromArray()...
}
```
Enregistrez ensuite ce type dans `api/src/Entity/IntegrationParamFactory.php`.

### Étape 2 : Implémenter le Connecteur
Créez `api/src/Integration/Connector/<Nom>Connector.php` :
```php
namespace App\Integration\Connector;

use App\Entity\Integration;
use App\Entity\IntegrationParamInterface;
use App\Integration\Dto\ConnectionTestResult;
use App\Service\ProxyResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitLabConnector implements IntegrationConnectorInterface, ProjectProviderConnectorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ProxyResolver $proxyResolver,
    ) {}

    public function supports(string $type): bool { return 'gitlab' === $type; }
    public function getType(): string { return 'gitlab'; }
    public function getName(): string { return 'GitLab'; }

    public function testConnection(Integration $integration): ConnectionTestResult { /* ... */ }
    public function checkHealth(Integration $integration, ?IntegrationParamInterface $param = null): ConnectionTestResult { /* ... */ }
    public function getLiveData(Integration $integration, ?IntegrationParamInterface $param = null): array { /* ... */ }
    public function getProjects(Integration $integration): array { /* ... */ }

    private function buildRequestOptions(Integration $integration): array
    {
        $options = [];
        $proxyOptions = $this->proxyResolver->resolveProxyOptions($integration);
        if (isset($proxyOptions['proxy'])) {
            $options['proxy'] = $proxyOptions['proxy'];
        }
        // Ajout des en-têtes d'authentification...
        return $options;
    }
}
```
Grâce à l'attribut `#[AutoconfigureTag('app.integration_connector')]` sur `IntegrationConnectorInterface`, le connecteur est automatiquement enregistré dans le conteneur Symfony sans modification de configuration YAML.

### Étape 3 : Écrire les Tests
Créez un test unitaire et d'intégration dans `api/tests/Integration/<Nom>ConnectorTest.php` utilisant `MockHttpClient` et `MockResponse` pour valider :
- Les réponses nominales (HTTP 200).
- Les erreurs d'authentification (HTTP 401 / 403).
- La bonne transmission des options de proxy résolues par `ProxyResolver`.
