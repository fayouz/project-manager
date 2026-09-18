<template>
  <div class="space-y-6 w-full">
    <!-- 1. En-tête du composant Show -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200 dark:border-neutral-800">
      <div class="flex items-center gap-3">
        <UButton
          v-if="showBack"
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          title="Retour"
          @click="emit('back')"
        />
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
              {{ item?.name || 'Détails du projet' }}
            </h1>
            <UBadge color="primary" variant="subtle" size="xs">
              Projet
            </UBadge>
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            Tableau de bord, détails et services DevOps associés
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <UButton
          color="primary"
          variant="outline"
          size="sm"
          icon="i-heroicons-plus"
          label="Associer un service"
          @click="openLinkModal()"
        />
        <UButton
          color="primary"
          variant="solid"
          size="sm"
          icon="i-heroicons-pencil-square"
          label="Modifier le projet"
          @click="emit('edit', item)"
        />
      </div>
    </div>

    <!-- 2. Sous-Menu de navigation (Tabs du Show) -->
    <div class="border-b border-neutral-200 dark:border-neutral-800 pb-2">
      <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none py-1">
        <UButton
          v-for="tab in subMenuTabs"
          :key="tab.id"
          :variant="currentTab === tab.id ? 'solid' : 'ghost'"
          :color="currentTab === tab.id ? 'primary' : 'neutral'"
          size="sm"
          :icon="tab.icon"
          class="shrink-0 font-medium transition"
          @click="setTab(tab.id)"
        >
          <span>{{ tab.label }}</span>
          <UBadge
            v-if="tab.badge !== undefined"
            :color="currentTab === tab.id ? 'neutral' : (tab.badgeColor || 'neutral')"
            variant="subtle"
            size="xs"
            class="ml-1 text-[10px]"
          >
            {{ tab.badge }}
          </UBadge>
        </UButton>
      </div>
    </div>

    <!-- Erreur globale si chargement échoue -->
    <UAlert
      v-if="error"
      color="error"
      title="Erreur de chargement"
      :description="error"
      icon="i-heroicons-exclamation-triangle"
    />

    <!-- État de chargement principal -->
    <div v-if="isLoading" class="p-12 text-center text-neutral-500">
      <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin mx-auto mb-3 text-primary-500" />
      <p class="text-sm font-medium">Chargement des données du projet...</p>
    </div>

    <!-- 3. Contenu de l'onglet actif -->
    <div v-else-if="item" class="space-y-6">
      <!-- Onglet 1 : Tableau de bord (Mini Dashboard) -->
      <div v-show="currentTab === 'dashboard'">
        <ProjectDashboard
          :project="item"
          :project-integrations="projectIntegrations"
          :all-integrations="allIntegrations"
          @switch-tab="setTab"
          @link-tool="openLinkModal"
          @edit-pi="openEditModal"
          @edit-project="emit('edit', item)"
        />
      </div>

      <!-- Onglet 2 : Gitea (Contenu) -->
      <div v-show="currentTab === 'gitea'">
        <ProjectGitea
          :project="item"
          :project-integration="giteaPi"
          :all-integrations="allIntegrations"
          @configure="setTab('settings', 'gitea')"
        />
      </div>

      <!-- Onglet 3 : SonarQube (Contenu) -->
      <div v-show="currentTab === 'sonarqube'">
        <ProjectSonarQube
          :project="item"
          :project-integration="sonarPi"
          :all-integrations="allIntegrations"
          @configure="setTab('settings', 'sonarqube')"
        />
      </div>

      <!-- Onglet 4 : Mantis BT (Contenu) -->
      <div v-show="currentTab === 'mantis'">
        <ProjectMantis
          :project="item"
          :project-integration="mantisPi"
          :all-integrations="allIntegrations"
          @configure="setTab('settings', 'mantis')"
        />
      </div>

      <!-- Onglet 5 : Jenkins CI (Contenu) -->
      <div v-show="currentTab === 'jenkins'">
        <ProjectJenkins
          :project="item"
          :project-integration="jenkinsPi"
          @configure="setTab('settings', 'jenkins')"
        />
      </div>

      <!-- Onglet 6 : Paramètres (Sous-layout avec menu vertical regroupant les intégrations) -->
      <div v-show="currentTab === 'settings'">
        <ProjectSettings
          :project="item"
          :project-integrations="projectIntegrations"
          :all-integrations="allIntegrations"
          :initial-sub-tab="currentSubTab"
          :is-loading="isLoadingIntegrations"
          @create-integration="openLinkModal"
          @edit-integration="openEditModal"
          @unlink-integration="handleUnlink"
          @tested="loadProjectIntegrations"
          @edit-project="emit('edit', item)"
          @subtab-changed="onSubTabChanged"
        />
      </div>
    </div>

    <!-- 4. Modale d'association / modification d'une intégration -->
    <UModal
      v-model:open="isModalOpen"
      :title="modalMode === 'create' ? 'Associer un service DevOps' : 'Modifier les paramètres'"
      :description="modalMode === 'create' ? 'Sélectionnez un service et configurez les paramètres de liaison.' : 'Mettez à jour les paramètres spécifiques du connecteur.'"
    >
      <template #body>
        <div class="space-y-4">
          <!-- Alerte d'erreur de formulaire -->
          <UAlert
            v-if="formError"
            color="error"
            :title="formError"
            icon="i-heroicons-exclamation-triangle"
            size="sm"
          />

          <!-- Choix de l'intégration (en création uniquement) -->
          <div v-if="modalMode === 'create'" class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Service / Outil à associer <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="formIntegrationIri"
              :items="availableIntegrationOptions"
              class="w-full"
              placeholder="Sélectionner une intégration"
            />
          </div>

          <div v-else class="p-3 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-xs flex items-center justify-between">
            <span class="text-neutral-500">Service :</span>
            <span class="font-bold text-neutral-900 dark:text-neutral-100">{{ selectedPiIntegrationName }}</span>
          </div>

          <!-- Champs de configuration dédiés selon le type -->
          <div v-if="selectedIntegrationType === 'gitea'" class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
              <UIcon name="i-heroicons-code-bracket" class="w-4 h-4 text-amber-500" />
              Paramètres Gitea
            </p>

            <div class="space-y-1">
              <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                Chemin du dépôt (ex: <code>organisation/projet</code>)
              </label>
              <UInput
                v-model="formParamFields.repository"
                placeholder="ex: my-org/my-repo"
                class="w-full font-mono text-xs"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                Branche par défaut
              </label>
              <UInput
                v-model="formParamFields.branch"
                placeholder="main"
                class="w-full font-mono text-xs"
              />
            </div>
          </div>

          <div v-else-if="selectedIntegrationType === 'sonarqube'" class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
              <UIcon name="i-heroicons-shield-check" class="w-4 h-4 text-blue-500" />
              Paramètres SonarQube
            </p>

            <div class="space-y-1">
              <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                Clé de projet SonarQube (<code>project_key</code>)
              </label>
              <UInput
                v-model="formParamFields.project_key"
                placeholder="ex: my-project-key"
                class="w-full font-mono text-xs"
              />
            </div>
          </div>

          <div v-else-if="selectedIntegrationType === 'mantis'" class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                <UIcon name="i-heroicons-bug-ant" class="w-4 h-4 text-emerald-500" />
                Paramètres Mantis BT
              </p>
              <div class="flex items-center gap-2">
                <UButton
                  v-if="!isManualMantisInput"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-arrow-path"
                  :loading="isLoadingMantisProjects"
                  title="Actualiser la liste des projets Mantis"
                  @click="reloadMantisProjects"
                />
                <UButton
                  variant="link"
                  color="neutral"
                  size="xs"
                  :label="isManualMantisInput ? 'Choisir dans la liste' : 'Saisir un ID manuellement'"
                  @click="isManualMantisInput = !isManualMantisInput"
                />
              </div>
            </div>

            <!-- Cas 1 : Saisie manuelle de l'ID -->
            <div v-if="isManualMantisInput" class="space-y-1">
              <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                Identifiant du projet Mantis (<code>project_id</code>)
              </label>
              <UInput
                v-model="formParamFields.project_id"
                placeholder="ex: 425"
                class="w-full font-mono text-xs"
              />
              <p class="text-[11px] text-neutral-400">
                Saisissez le numéro d'identifiant numérique du projet Mantis.
              </p>
            </div>

            <!-- Cas 2 : Sélection dans la liste déroulante -->
            <div v-else class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                  Sélection du projet Mantis <span class="text-error-500">*</span>
                </label>
                <span v-if="formParamFields.project_id" class="text-[11px] font-mono text-neutral-500 dark:text-neutral-400">
                  ID : <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ formParamFields.project_id }}</span>
                </span>
              </div>

              <!-- Erreur de chargement des projets -->
              <div v-if="mantisProjectsError" class="space-y-2">
                <UAlert
                  color="warning"
                  variant="subtle"
                  title="Impossible de récupérer les projets Mantis"
                  :description="mantisProjectsError"
                  icon="i-heroicons-exclamation-triangle"
                  size="xs"
                />
                <div class="flex items-center gap-2">
                  <UButton
                    size="xs"
                    color="primary"
                    variant="soft"
                    icon="i-heroicons-arrow-path"
                    label="Réessayer"
                    :loading="isLoadingMantisProjects"
                    @click="reloadMantisProjects"
                  />
                  <UButton
                    size="xs"
                    color="neutral"
                    variant="ghost"
                    label="Passer en saisie manuelle"
                    @click="isManualMantisInput = true"
                  />
                </div>
              </div>

              <!-- Liste déroulante des projets -->
              <div v-else class="space-y-1">
                <USelectMenu
                  v-model="formParamFields.project_id"
                  :items="mantisSelectItems"
                  value-key="value"
                  label-key="label"
                  :loading="isLoadingMantisProjects"
                  :disabled="isLoadingMantisProjects"
                  placeholder="Rechercher ou sélectionner un projet Mantis..."
                  icon="i-heroicons-bug-ant"
                  class="w-full"
                />
                <div class="flex items-center justify-between text-[11px] text-neutral-400">
                  <span>{{ mantisSelectItems.length }} projet(s) disponible(s)</span>
                  <span v-if="isLoadingMantisProjects" class="flex items-center gap-1 text-emerald-600">
                    <UIcon name="i-heroicons-arrow-path" class="w-3 h-3 animate-spin" />
                    Chargement des projets...
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="selectedIntegrationType === 'jenkins'" class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-4 h-4 text-sky-500" />
                Dossier des jobs Jenkins
              </p>
              <div class="flex items-center gap-2">
                <UButton
                  v-if="!isManualJenkinsInput"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-arrow-path"
                  :loading="isLoadingJenkinsFolders"
                  title="Actualiser la liste des dossiers Jenkins"
                  @click="reloadJenkinsFolders"
                />
                <UButton
                  variant="link"
                  color="neutral"
                  size="xs"
                  :label="isManualJenkinsInput ? 'Choisir dans la liste' : 'Saisir manuellement le dossier'"
                  @click="isManualJenkinsInput = !isManualJenkinsInput"
                />
              </div>
            </div>

            <!-- Mode 1 : Saisie manuelle du chemin du dossier -->
            <div v-if="isManualJenkinsInput" class="space-y-2">
              <div class="space-y-1">
                <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                  Chemin du dossier Jenkins (ex: <code>job/REGAZ/job/RegazScrapper/</code> ou <code>REGAZ/RegazScrapper</code>)
                </label>
                <div class="flex items-center gap-2">
                  <UInput
                    v-model="formParamFields.folder"
                    placeholder="ex: job/REGAZ/job/RegazScrapper/"
                    class="w-full font-mono text-xs"
                    @change="onJenkinsFolderChanged(formParamFields.folder)"
                    @blur="onJenkinsFolderChanged(formParamFields.folder)"
                  />
                  <UButton
                    size="xs"
                    color="neutral"
                    variant="outline"
                    icon="i-heroicons-magnifying-glass"
                    :loading="isLoadingJenkinsJobs"
                    title="Détecter les jobs"
                    @click="onJenkinsFolderChanged(formParamFields.folder)"
                  />
                </div>
              </div>
              <p class="text-[11px] text-neutral-400">
                Saisissez le chemin du dossier sur Jenkins. Tous les jobs contenus dans ce dossier seront automatiquement référencés.
              </p>
            </div>

            <!-- Mode 2 : Menu déroulant avec recherche dynamique -->
            <div v-else class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                  Sélection du dossier Jenkins <span class="text-error-500">*</span>
                </label>
                <span v-if="formParamFields.folder" class="text-[11px] font-mono text-neutral-500 dark:text-neutral-400 truncate max-w-[200px]">
                  {{ formParamFields.folder }}
                </span>
              </div>

              <USelectMenu
                :model-value="formParamFields.folder"
                :items="jenkinsSelectItems"
                value-key="value"
                searchable
                searchable-placeholder="Rechercher un dossier (ex: REGAZ)..."
                placeholder="Rechercher ou sélectionner un dossier Jenkins..."
                icon="i-heroicons-folder"
                class="w-full"
                @update:model-value="onJenkinsFolderChanged"
              />
              <div class="flex items-center justify-between text-[11px] text-neutral-400">
                <span>{{ jenkinsSelectItems.length }} dossier(s) disponible(s)</span>
                <span v-if="isLoadingJenkinsFolders" class="flex items-center gap-1 text-sky-600">
                  <UIcon name="i-heroicons-arrow-path" class="w-3 h-3 animate-spin" />
                  Chargement des dossiers...
                </span>
              </div>
            </div>

            <!-- Détection et prévisualisation des jobs référencés -->
            <div v-if="isLoadingJenkinsJobs" class="p-3 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-200 dark:border-sky-800/40 text-xs text-sky-700 dark:text-sky-300 flex items-center gap-2">
              <UIcon name="i-heroicons-arrow-path" class="w-4 h-4 animate-spin shrink-0" />
              <span>Interrogation de Jenkins et référencement des jobs du dossier...</span>
            </div>

            <div v-else-if="discoveredJenkinsJobs.length > 0" class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                  <UIcon name="i-heroicons-check-circle" class="w-4 h-4" />
                  {{ discoveredJenkinsJobs.length }} job(s) référencé(s) dans le connecteur :
                </span>
                <UBadge color="success" variant="subtle" size="xs">
                  Prêt à enregistrer
                </UBadge>
              </div>
              <div class="flex items-center gap-1.5 flex-wrap max-h-32 overflow-y-auto pr-1">
                <UBadge
                  v-for="job in discoveredJenkinsJobs"
                  :key="job.name"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-[11px]"
                >
                  {{ job.displayName || job.name }}
                </UBadge>
              </div>
            </div>

            <div v-else-if="formParamFields.folder && !isLoadingJenkinsJobs" class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 text-[11px] text-amber-700 dark:text-amber-300 flex items-center gap-2">
              <UIcon name="i-heroicons-information-circle" class="w-4 h-4 shrink-0" />
              <span>Les jobs de ce dossier seront automatiquement scannés et enregistrés dans le connecteur lors de la validation.</span>
            </div>
          </div>

          <!-- Paramètres personnalisés / avancés -->
          <div class="pt-2 border-t border-neutral-200 dark:border-neutral-800 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Paramètres personnalisés additionnels
              </span>
              <UButton
                size="xs"
                variant="ghost"
                color="primary"
                icon="i-heroicons-plus"
                label="Ajouter une paire"
                @click="addCustomParamRow"
              />
            </div>

            <div v-if="customParamRows.length === 0" class="text-[11px] text-neutral-400 italic">
              Aucun paramètre personnalisé supplémentaire.
            </div>

            <div
              v-for="(row, idx) in customParamRows"
              :key="idx"
              class="flex items-center gap-2"
            >
              <UInput
                v-model="row.key"
                placeholder="Clé (ex: env)"
                size="xs"
                class="w-1/2 font-mono"
              />
              <UInput
                v-model="row.value"
                placeholder="Valeur"
                size="xs"
                class="w-1/2 font-mono"
              />
              <UButton
                variant="ghost"
                color="error"
                size="xs"
                icon="i-heroicons-trash"
                @click="removeCustomParamRow(idx)"
              />
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex items-center justify-end gap-2 w-full">
          <UButton
            variant="ghost"
            color="neutral"
            size="sm"
            label="Annuler"
            @click="isModalOpen = false"
          />
          <UButton
            color="primary"
            size="sm"
            :loading="isSubmitting"
            :label="modalMode === 'create' ? 'Associer le service' : 'Enregistrer'"
            @click="submitModal"
          />
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import { resolveApiUrl } from "~/utils/config";
import { useFetchItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { useIntegrationProjectsStore } from "~/stores/integration/projects";
import {
  getIntegration,
  getIntegrationType,
  getIntegrationName,
} from "~/utils/integration";

// Composants dédiés
import ProjectDashboard from "./ProjectDashboard.vue";
import ProjectGitea from "./ProjectGitea.vue";
import ProjectSonarQube from "./ProjectSonarQube.vue";
import ProjectMantis from "./ProjectMantis.vue";
import ProjectJenkins from "./ProjectJenkins.vue";
import ProjectSettings from "./ProjectSettings.vue";

const props = defineProps<{
  id?: string;
  item?: Project;
  showBack?: boolean;
}>();

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: Project): void;
}>();

const route = useRoute();
const router = useRouter();

// Onglet actif
const currentTab = ref<string>((route.query.tab as string) || "dashboard");
const currentSubTab = ref<string>((route.query.subtab as string) || "integrations");

// Alias de compatibilité ascendante
if (currentTab.value === "integrations") {
  currentTab.value = "settings";
  currentSubTab.value = "integrations";
} else if (currentTab.value === "details") {
  currentTab.value = "settings";
  currentSubTab.value = "general";
} else if (currentTab.value === "members") {
  currentTab.value = "settings";
  currentSubTab.value = "members";
} else if (currentTab.value === "teams") {
  currentTab.value = "settings";
  currentSubTab.value = "teams";
}

function setTab(tabId: string, subTabId?: string) {
  if (tabId === "integrations") {
    currentTab.value = "settings";
    currentSubTab.value = "integrations";
  } else if (tabId === "details") {
    currentTab.value = "settings";
    currentSubTab.value = "general";
  } else if (tabId === "members") {
    currentTab.value = "settings";
    currentSubTab.value = "members";
  } else if (tabId === "teams") {
    currentTab.value = "settings";
    currentSubTab.value = "teams";
  } else {
    currentTab.value = tabId;
  }

  if (subTabId) {
    currentSubTab.value = subTabId;
  }

  router.replace({
    query: {
      ...route.query,
      tab: currentTab.value === "dashboard" ? undefined : currentTab.value,
      subtab: currentTab.value === "settings" ? currentSubTab.value : undefined,
    },
  });
}

function onSubTabChanged(subTabId: string) {
  currentSubTab.value = subTabId;
  router.replace({
    query: {
      ...route.query,
      tab: "settings",
      subtab: subTabId,
    },
  });
}

watch(
  () => [route.query.tab, route.query.subtab],
  ([newTab, newSubTab]) => {
    if (newTab === "integrations") {
      currentTab.value = "settings";
      currentSubTab.value = "integrations";
    } else if (newTab === "details") {
      currentTab.value = "settings";
      currentSubTab.value = "general";
    } else if (newTab === "members") {
      currentTab.value = "settings";
      currentSubTab.value = "members";
    } else if (newTab === "teams") {
      currentTab.value = "settings";
      currentSubTab.value = "teams";
    } else if (newTab && typeof newTab === "string") {
      currentTab.value = newTab;
    } else if (!newTab) {
      currentTab.value = "dashboard";
    }

    if (newSubTab && typeof newSubTab === "string") {
      currentSubTab.value = newSubTab;
    }
  }
);

// État local du projet
const item = ref<Project | undefined>(props.item);
const isLoading = ref(false);
const error = ref<string | undefined>(undefined);

const currentId = computed(() => {
  return props.id || (route.params.id as string) || "";
});

// État des intégrations
const projectIntegrations = ref<ProjectIntegration[]>([]);
const allIntegrations = ref<Integration[]>([]);
const isLoadingIntegrations = ref(false);

// Raccourcis pour les intégrations dédiées
const giteaPi = computed(() => {
  return projectIntegrations.value.find((pi) => getIntegrationType(pi) === "gitea") || null;
});

const sonarPi = computed(() => {
  return projectIntegrations.value.find((pi) => getIntegrationType(pi) === "sonarqube") || null;
});

const mantisPi = computed(() => {
  return projectIntegrations.value.find((pi) => getIntegrationType(pi) === "mantis") || null;
});

const jenkinsPi = computed(() => {
  return projectIntegrations.value.find((pi) => getIntegrationType(pi) === "jenkins") || null;
});

// Onglets du sous-menu avec badges dynamiques
const subMenuTabs = computed(() => [
  {
    id: "dashboard",
    label: "Tableau de bord",
    icon: "i-heroicons-squares-2x2",
  },
  {
    id: "gitea",
    label: "Gitea",
    icon: "i-heroicons-code-bracket",
    badge: giteaPi.value ? "Lié" : undefined,
    badgeColor: "warning" as const,
  },
  {
    id: "sonarqube",
    label: "SonarQube",
    icon: "i-heroicons-shield-check",
    badge: sonarPi.value ? "Lié" : undefined,
    badgeColor: "primary" as const,
  },
  {
    id: "mantis",
    label: "Mantis BT",
    icon: "i-heroicons-bug-ant",
    badge: mantisPi.value ? "Lié" : undefined,
    badgeColor: "success" as const,
  },
  {
    id: "jenkins",
    label: "Jenkins CI",
    icon: "i-heroicons-arrow-path-rounded-square",
    badge: jenkinsPi.value ? "Lié" : undefined,
    badgeColor: "info" as const,
  },
  {
    id: "settings",
    label: "Paramètres",
    icon: "i-heroicons-cog-6-tooth",
    badge: projectIntegrations.value.length ? `${projectIntegrations.value.length}` : undefined,
    badgeColor: "neutral" as const,
  },
]);

// Modale de création / édition des intégrations
const isModalOpen = ref(false);
const modalMode = ref<"create" | "edit">("create");
const editingPi = ref<ProjectIntegration | null>(null);
const isSubmitting = ref(false);
const formError = ref<string | null>(null);
const formIntegrationIri = ref<string>("");

// Store et gestion des projets Mantis
const integrationProjectsStore = useIntegrationProjectsStore();
const isManualMantisInput = ref(false);

const isLoadingMantisProjects = computed(() => integrationProjectsStore.isLoading);
const mantisProjectsError = computed(() => integrationProjectsStore.error);
const mantisProjects = computed(() => integrationProjectsStore.projects);

const mantisSelectItems = computed(() => {
  const options = mantisProjects.value.map((p) => ({
    label: `${p.name} (#${p.id})`,
    value: String(p.id),
  }));

  if (
    formParamFields.value.project_id &&
    !options.some((o) => o.value === String(formParamFields.value.project_id))
  ) {
    options.unshift({
      label: `Projet #${formParamFields.value.project_id} (actuel)`,
      value: String(formParamFields.value.project_id),
    });
  }

  return options;
});

async function loadMantisProjectsIfNeeded() {
  if (selectedIntegrationType.value !== "mantis") return;
  const iri = formIntegrationIri.value;
  const integrationId = getIdFromIri(iri);
  if (!integrationId) return;

  await integrationProjectsStore.fetchProjects(integrationId);
}

function reloadMantisProjects() {
  loadMantisProjectsIfNeeded();
}

// Store et gestion des dossiers / jobs Jenkins
const isManualJenkinsInput = ref(false);
const isLoadingJenkinsFolders = ref(false);
const isLoadingJenkinsJobs = ref(false);
const discoveredJenkinsJobs = ref<any[]>([]);

const jenkinsSelectItems = computed(() => {
  const options = integrationProjectsStore.projects.map((p) => ({
    label: `${p.name}`,
    value: p.id,
  }));

  if (
    formParamFields.value.folder &&
    !options.some((o) => o.value === formParamFields.value.folder)
  ) {
    options.unshift({
      label: `${formParamFields.value.folder} (personnalisé)`,
      value: formParamFields.value.folder,
    });
  }

  return options;
});

async function loadJenkinsFoldersIfNeeded() {
  if (selectedIntegrationType.value !== "jenkins") return;
  const iri = formIntegrationIri.value;
  const integrationId = getIdFromIri(iri);
  if (!integrationId) return;

  isLoadingJenkinsFolders.value = true;
  try {
    await integrationProjectsStore.fetchProjects(integrationId);
  } finally {
    isLoadingJenkinsFolders.value = false;
  }
}

function reloadJenkinsFolders() {
  loadJenkinsFoldersIfNeeded();
}

async function onJenkinsFolderChanged(newFolder: string) {
  formParamFields.value.folder = newFolder;
  if (!newFolder) {
    discoveredJenkinsJobs.value = [];
    return;
  }
  const iri = formIntegrationIri.value;
  const integrationId = getIdFromIri(iri);
  if (!integrationId) return;

  isLoadingJenkinsJobs.value = true;
  try {
    const jobs = await integrationProjectsStore.fetchJobs(integrationId, newFolder);
    discoveredJenkinsJobs.value = jobs;
  } catch {
    discoveredJenkinsJobs.value = [];
  } finally {
    isLoadingJenkinsJobs.value = false;
  }
}

const formParamFields = ref({
  repository: "",
  branch: "main",
  project_key: "",
  job: "",
  folder: "",
  project_id: "",
});

const customParamRows = ref<{ key: string; value: string }[]>([]);

const availableIntegrationOptions = computed(() => {
  return allIntegrations.value.map((i) => ({
    label: `${i.name} (${i.type?.toUpperCase()})`,
    value: i["@id"],
  }));
});

const selectedIntegrationType = computed(() => {
  if (modalMode.value === "create") {
    const found = allIntegrations.value.find((i) => i["@id"] === formIntegrationIri.value);
    return found?.type?.toLowerCase() || "";
  }
  if (editingPi.value) {
    return getIntegrationType(editingPi.value);
  }
  return "";
});

watch(
  [selectedIntegrationType, formIntegrationIri],
  ([newType, newIri]) => {
    if (newType === "mantis" && newIri) {
      loadMantisProjectsIfNeeded();
    } else if (newType === "jenkins" && newIri) {
      loadJenkinsFoldersIfNeeded();
    }
  }
);

const selectedPiIntegrationName = computed(() => {
  if (!editingPi.value) return "";
  return getIntegrationName(editingPi.value);
});

function openLinkModal(defaultType?: string) {
  modalMode.value = "create";
  editingPi.value = null;
  formError.value = null;
  isManualMantisInput.value = false;
  isManualJenkinsInput.value = false;
  discoveredJenkinsJobs.value = [];

  if (defaultType) {
    const match = allIntegrations.value.find(
      (i) => i.type?.toLowerCase() === defaultType.toLowerCase()
    );
    formIntegrationIri.value = match?.["@id"] || allIntegrations.value[0]?.["@id"] || "";
  } else {
    formIntegrationIri.value = allIntegrations.value[0]?.["@id"] || "";
  }

  formParamFields.value = {
    repository: "",
    branch: "main",
    project_key: "",
    job: "",
    folder: "",
    project_id: "",
  };
  customParamRows.value = [];
  isModalOpen.value = true;

  if (defaultType?.toLowerCase() === "mantis" || selectedIntegrationType.value === "mantis") {
    nextTick(() => {
      loadMantisProjectsIfNeeded();
    });
  } else if (defaultType?.toLowerCase() === "jenkins" || selectedIntegrationType.value === "jenkins") {
    nextTick(() => {
      loadJenkinsFoldersIfNeeded();
    });
  }
}

function openEditModal(pi: ProjectIntegration) {
  modalMode.value = "edit";
  editingPi.value = pi;
  formError.value = null;
  isManualMantisInput.value = false;
  isManualJenkinsInput.value = false;
  formIntegrationIri.value = typeof pi.integration === "object" ? pi.integration?.["@id"] : (pi.integration || "");

  const params = pi.parameters || {};
  formParamFields.value = {
    repository: params.repository || "",
    branch: params.branch || "",
    project_key: params.project_key || "",
    job: params.job || params.job_name || "",
    folder: params.folder || params.folder_path || params.job_folder || "",
    project_id: params.project_id !== undefined ? String(params.project_id) : "",
  };

  if (params.jobs && Array.isArray(params.jobs)) {
    discoveredJenkinsJobs.value = params.jobs;
  } else {
    discoveredJenkinsJobs.value = [];
  }

  const standardKeys = ["repository", "branch", "project_key", "job", "job_name", "folder", "folder_path", "job_folder", "jobs", "jobs_count", "project_id", "project_name"];
  customParamRows.value = Object.entries(params)
    .filter(([k]) => !standardKeys.includes(k))
    .map(([key, value]) => ({ key, value: String(value) }));

  isModalOpen.value = true;

  if (getIntegrationType(pi) === "mantis") {
    nextTick(() => {
      loadMantisProjectsIfNeeded();
    });
  } else if (getIntegrationType(pi) === "jenkins") {
    nextTick(() => {
      loadJenkinsFoldersIfNeeded();
      if (formParamFields.value.folder && discoveredJenkinsJobs.value.length === 0) {
        onJenkinsFolderChanged(formParamFields.value.folder);
      }
    });
  }
}

function addCustomParamRow() {
  customParamRows.value.push({ key: "", value: "" });
}

function removeCustomParamRow(idx: number) {
  customParamRows.value.splice(idx, 1);
}

async function submitModal() {
  const projectIri = item.value?.["@id"] || (currentId.value ? `/api/projects/${currentId.value}` : null);
  if (!projectIri) {
    formError.value = "Projet introuvable.";
    return;
  }

  if (modalMode.value === "create" && !formIntegrationIri.value) {
    formError.value = "Veuillez sélectionner une intégration.";
    return;
  }

  const finalParameters: Record<string, any> = {};
  const type = selectedIntegrationType.value;

  if (type === "gitea") {
    if (formParamFields.value.repository?.trim()) {
      finalParameters.repository = formParamFields.value.repository.trim();
    }
    if (formParamFields.value.branch?.trim()) {
      finalParameters.branch = formParamFields.value.branch.trim();
    }
  } else if (type === "sonarqube") {
    if (formParamFields.value.project_key?.trim()) {
      finalParameters.project_key = formParamFields.value.project_key.trim();
    }
  } else if (type === "jenkins") {
    if (formParamFields.value.folder?.trim()) {
      const folderVal = formParamFields.value.folder.trim();
      finalParameters.folder = folderVal;
      finalParameters.job = folderVal;
      finalParameters.job_name = folderVal;
      if (discoveredJenkinsJobs.value.length > 0) {
        finalParameters.jobs = discoveredJenkinsJobs.value;
        finalParameters.jobs_count = discoveredJenkinsJobs.value.length;
      }
    } else if (formParamFields.value.job?.trim()) {
      finalParameters.job = formParamFields.value.job.trim();
      finalParameters.job_name = formParamFields.value.job.trim();
    }
  } else if (type === "mantis") {
    if (formParamFields.value.project_id?.trim()) {
      finalParameters.project_id = formParamFields.value.project_id.trim();
      const matched = mantisProjects.value.find((p) => String(p.id) === finalParameters.project_id);
      if (matched) {
        finalParameters.project_name = matched.name;
      }
    }
  }

  for (const row of customParamRows.value) {
    if (row.key?.trim()) {
      finalParameters[row.key.trim()] = row.value?.trim() || "";
    }
  }

  isSubmitting.value = true;
  formError.value = null;

  try {
    if (modalMode.value === "create") {
      await $fetch(resolveApiUrl("/project_integrations"), {
        method: "POST",
        headers: {
          "Content-Type": "application/ld+json",
          Accept: "application/ld+json",
        },
        body: {
          project: projectIri,
          integration: formIntegrationIri.value,
          parameters: finalParameters,
        },
      });
    } else if (editingPi.value?.["@id"]) {
      await $fetch(resolveApiUrl(editingPi.value["@id"]), {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          Accept: "application/ld+json",
        },
        body: {
          parameters: finalParameters,
        },
      });
    }

    isModalOpen.value = false;
    await loadProjectIntegrations();
  } catch (err: any) {
    formError.value = err?.data?.["hydra:description"] || err?.message || "Erreur lors de l'enregistrement.";
  } finally {
    isSubmitting.value = false;
  }
}

async function handleUnlink(pi: ProjectIntegration) {
  if (!pi["@id"]) return;
  const integName = getIntegrationName(pi);
  const ok = confirm(`Êtes-vous sûr de vouloir dissocier l'intégration "${integName}" de ce projet ?`);
  if (!ok) return;

  try {
    await $fetch(resolveApiUrl(pi["@id"]), {
      method: "DELETE",
    });
    await loadProjectIntegrations();
  } catch (err: any) {
    alert(err?.message || "Erreur lors de la suppression de la liaison.");
  }
}

// Chargement des données
async function load() {
  if (props.item) {
    item.value = props.item;
  } else if (currentId.value) {
    isLoading.value = true;
    error.value = undefined;
    try {
      const data = await useFetchItem<Project>(`projects/${currentId.value}`);
      item.value = data.retrieved.value;
      if (data.error.value) {
        error.value = data.error.value?.message || String(data.error.value);
      }
    } catch (err: any) {
      error.value = err.message || "Erreur de chargement";
    } finally {
      isLoading.value = false;
    }
  }

  await Promise.all([loadProjectIntegrations(), loadAllIntegrations()]);
}

async function loadProjectIntegrations() {
  const projectIri = item.value?.["@id"] || (currentId.value ? `/api/projects/${currentId.value}` : null);
  if (!projectIri) return;

  isLoadingIntegrations.value = true;
  try {
    const res: any = await $fetch(resolveApiUrl("/project_integrations"), {
      params: { project: projectIri },
      headers: { Accept: "application/ld+json" },
    });
    projectIntegrations.value = res?.member || res?.["hydra:member"] || [];
  } catch {
    //
  } finally {
    isLoadingIntegrations.value = false;
  }
}

async function loadAllIntegrations() {
  try {
    const res: any = await $fetch(resolveApiUrl("/integrations"), {
      headers: { Accept: "application/ld+json" },
    });
    allIntegrations.value = res?.member || res?.["hydra:member"] || [];
  } catch {
    //
  }
}

await load();
watch(() => currentId.value, () => load());
watch(
  () => props.item,
  (val) => {
    if (val) {
      item.value = val;
      loadProjectIntegrations();
    }
  }
);
</script>
