<template>
  <div class="space-y-6">
    <!-- Carte Principale : Détails du Projet -->
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 flex items-center justify-center shrink-0">
              <UIcon name="i-heroicons-folder" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                {{ item?.name || 'Détails du projet' }}
              </h3>
              <p class="text-xs text-neutral-500">Informations générales et configuration</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <UButton
              v-if="showBack"
              variant="ghost"
              color="neutral"
              icon="i-heroicons-arrow-left"
              size="sm"
              label="Retour"
              @click="handleBack"
            />
            <UButton
              variant="soft"
              color="primary"
              icon="i-heroicons-pencil-square"
              size="sm"
              label="Modifier"
              @click="emit('edit', item)"
            />
          </div>
        </div>
      </template>

      <div v-if="isLoading" class="flex justify-center p-6">
        <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
      </div>

      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="error"
        class="mb-4"
      />

      <div v-if="item" class="divide-y divide-neutral-200 dark:divide-neutral-800">
        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
          <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Identifiant IRI</dt>
          <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono text-xs">
            {{ item['@id'] }}
          </dd>
        </div>
        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
          <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Nom du projet</dt>
          <dd class="mt-1 text-sm font-medium text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
            {{ item.name }}
          </dd>
        </div>
        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
          <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Organisation</dt>
          <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
            {{ formatOrganisation(item.organisation) }}
          </dd>
        </div>
      </div>
    </UCard>

    <!-- Carte Intégrations Associées -->
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-puzzle-piece" class="size-5 text-primary-500" />
            <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
              Intégrations associées
            </h3>
            <UBadge color="primary" variant="subtle" size="xs">
              {{ projectIntegrations.length }}
            </UBadge>
          </div>
          <UButton
            color="primary"
            variant="soft"
            size="xs"
            icon="i-heroicons-plus"
            label="Associer une intégration"
            @click="openLinkModal()"
          />
        </div>
      </template>

      <!-- Chargement des intégrations -->
      <div v-if="isLoadingIntegrations" class="flex justify-center items-center py-8 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-5 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des intégrations...</span>
      </div>

      <!-- Erreur de chargement -->
      <UAlert
        v-else-if="integrationError"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="integrationError"
        class="mb-4"
      />

      <!-- Aucune intégration associée -->
      <div
        v-else-if="projectIntegrations.length === 0"
        class="text-center py-8 px-4 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl space-y-3"
      >
        <UIcon name="i-heroicons-link-slash" class="mx-auto size-10 text-neutral-400" />
        <div>
          <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
            Aucune intégration associée à ce projet
          </h4>
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 max-w-md mx-auto">
            Connectez ce projet à des outils externes (Gitea, SonarQube, Jenkins, Mantis) et configurez les paramètres spécifiques (chemin de dépôt, clé de projet, etc.).
          </p>
        </div>
        <UButton
          size="xs"
          color="primary"
          icon="i-heroicons-plus"
          label="Associer une intégration maintenant"
          @click="openLinkModal()"
        />
      </div>

      <!-- Liste des intégrations liées -->
      <div v-else class="space-y-4">
        <div
          v-for="pi in projectIntegrations"
          :key="pi['@id'] || pi.id"
          class="border border-neutral-200 dark:border-neutral-800 rounded-xl p-4 bg-neutral-50/50 dark:bg-neutral-900/50 hover:border-neutral-300 dark:hover:border-neutral-700 transition-colors"
        >
          <!-- En-tête de l'intégration liée -->
          <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
              <div
                class="size-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                :class="getTypeBgClass(getIntegrationType(pi))"
              >
                <UIcon :name="getTypeIcon(getIntegrationType(pi))" class="size-5" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
                    {{ getIntegrationName(pi) }}
                  </h4>
                  <UBadge
                    :color="getTypeBadgeColor(getIntegrationType(pi))"
                    variant="subtle"
                    size="xs"
                    class="font-mono text-[11px]"
                  >
                    {{ getIntegrationType(pi)?.toUpperCase() }}
                  </UBadge>
                </div>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-neutral-500">
                  <span v-if="getServerHost(pi)" class="flex items-center gap-1">
                    <UIcon name="i-heroicons-server" class="size-3.5" />
                    {{ getServerHost(pi) }}
                  </span>
                  <span v-if="getIntegrationStatus(pi)" class="flex items-center gap-1">
                    <span
                      class="size-2 rounded-full inline-block"
                      :class="getStatusDotClass(getIntegrationStatus(pi))"
                    />
                    {{ getStatusLabel(getIntegrationStatus(pi)) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center gap-1.5 ml-auto">
              <UButton
                v-if="getExternalUrl(pi)"
                as="a"
                :href="getExternalUrl(pi)!"
                target="_blank"
                rel="noopener noreferrer"
                variant="soft"
                color="primary"
                size="xs"
                icon="i-heroicons-arrow-top-right-on-square"
                label="Ouvrir dans l'outil"
              />
              <UButton
                variant="ghost"
                color="neutral"
                size="xs"
                icon="i-heroicons-pencil-square"
                aria-label="Modifier les paramètres"
                title="Modifier les paramètres"
                @click="openEditModal(pi)"
              />
              <UButton
                variant="ghost"
                color="error"
                size="xs"
                icon="i-heroicons-trash"
                aria-label="Dissocier"
                title="Dissocier l'intégration"
                :loading="deletingId === pi['@id']"
                @click="handleUnlink(pi)"
              />
            </div>
          </div>

          <!-- Section des Paramètres spécifiques -->
          <div class="mt-4 pt-3 border-t border-neutral-200 dark:border-neutral-800">
            <h5 class="text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
              <UIcon name="i-heroicons-adjustments-horizontal" class="size-3.5" />
              Paramètres spécifiques configurés
            </h5>

            <div v-if="hasParameters(pi)" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <!-- Paramètre principal selon le type -->
              <div
                v-for="(val, key) in (pi.parameters || {})"
                :key="key"
                class="flex items-start justify-between p-2 rounded-lg bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700/60"
              >
                <div class="space-y-0.5">
                  <span class="text-neutral-500 text-[11px] font-medium block">
                    {{ formatParamKey(String(key), getIntegrationType(pi)) }}
                  </span>
                  <span class="font-mono font-medium text-neutral-900 dark:text-neutral-100 select-all">
                    {{ val }}
                  </span>
                </div>
                <UBadge
                  v-if="isPrimaryParam(String(key), getIntegrationType(pi))"
                  color="primary"
                  variant="outline"
                  size="xs"
                  class="text-[10px]"
                >
                  Principal
                </UBadge>
              </div>
            </div>

            <div v-else class="text-xs text-neutral-400 italic py-1">
              Aucun paramètre spécifique configuré (intégration globale).
            </div>
          </div>
        </div>
      </div>
    </UCard>

    <!-- Modale d'Association / Modification d'une Intégration de Projet -->
    <UModal
      v-model:open="isModalOpen"
      :title="modalMode === 'create' ? 'Associer une intégration au projet' : 'Modifier les paramètres d\'intégration'"
    >
      <template #body>
        <form @submit.prevent="submitModal" class="space-y-4">
          <UAlert
            v-if="formError"
            color="error"
            variant="subtle"
            icon="i-heroicons-exclamation-triangle"
            :title="formError"
          />

          <!-- Sélection de l'intégration (seulement en création) -->
          <UFormField
            v-if="modalMode === 'create'"
            label="Intégration externe"
            description="Sélectionnez l'outil externe à associer à ce projet."
            required
          >
            <USelect
              v-model="formIntegrationIri"
              :items="availableIntegrationOptions"
              placeholder="Sélectionner une intégration..."
              class="w-full"
            />
          </UFormField>

          <div v-else class="p-3 bg-neutral-100 dark:bg-neutral-800 rounded-lg flex items-center gap-3">
            <div
              class="size-8 rounded-lg flex items-center justify-center shrink-0"
              :class="getTypeBgClass(selectedIntegrationType)"
            >
              <UIcon :name="getTypeIcon(selectedIntegrationType)" class="size-4" />
            </div>
            <div>
              <div class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
                {{ selectedPiIntegrationName }}
              </div>
              <div class="text-xs text-neutral-500 font-mono">
                Type : {{ selectedIntegrationType?.toUpperCase() }}
              </div>
            </div>
          </div>

          <!-- Champs de paramètres dynamiques selon le type d'intégration -->
          <div v-if="selectedIntegrationType" class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <h4 class="text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider">
              Paramètres spécifiques au connecteur ({{ selectedIntegrationType?.toUpperCase() }})
            </h4>

            <!-- GITEA : Chemin du dépôt & branche -->
            <template v-if="selectedIntegrationType === 'gitea'">
              <UFormField
                label="Chemin Gitea du projet (Dépôt)"
                description="Exemple : organisation/nom-du-projet ou utilisateur/depot"
                required
              >
                <UInput
                  v-model="formParamFields.repository"
                  placeholder="bm-energies/scrapper"
                  icon="i-heroicons-code-bracket"
                  class="w-full font-mono text-sm"
                />
              </UFormField>

              <UFormField
                label="Branche par défaut (optionnel)"
                description="Exemple : main, master ou develop"
              >
                <UInput
                  v-model="formParamFields.branch"
                  placeholder="main"
                  icon="i-heroicons-variable"
                  class="w-full font-mono text-sm"
                />
              </UFormField>
            </template>

            <!-- SONARQUBE : Clé du projet -->
            <template v-else-if="selectedIntegrationType === 'sonarqube'">
              <UFormField
                label="Clé du projet SonarQube (Project Key)"
                description="Exemple : bme.scrapper ou identifiant unique dans SonarQube"
                required
              >
                <UInput
                  v-model="formParamFields.project_key"
                  placeholder="bme.scrapper"
                  icon="i-heroicons-shield-check"
                  class="w-full font-mono text-sm"
                />
              </UFormField>
            </template>

            <!-- JENKINS : Nom du Job -->
            <template v-else-if="selectedIntegrationType === 'jenkins'">
              <UFormField
                label="Nom du Job Jenkins"
                description="Exemple : scrapper-build ou pipeline-master"
                required
              >
                <UInput
                  v-model="formParamFields.job"
                  placeholder="scrapper-pipeline"
                  icon="i-heroicons-arrow-path-rounded-square"
                  class="w-full font-mono text-sm"
                />
              </UFormField>
            </template>

            <!-- MANTIS : ID ou Nom du projet -->
            <template v-else-if="selectedIntegrationType === 'mantis'">
              <UFormField
                label="ID ou Nom du projet Mantis"
                description="Exemple : 29 ou Scrapper"
                required
              >
                <UInput
                  v-model="formParamFields.project_id"
                  placeholder="Scrapper"
                  icon="i-heroicons-bug-ant"
                  class="w-full font-mono text-sm"
                />
              </UFormField>
            </template>

            <!-- Paramètres Personnalisés Additionnels -->
            <div class="pt-2">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-neutral-500">Autres paramètres personnalisés</span>
                <UButton
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-plus"
                  label="Ajouter un paramètre"
                  @click="addCustomParamRow"
                />
              </div>

              <div v-for="(row, idx) in customParamRows" :key="idx" class="flex items-center gap-2 mb-2">
                <UInput
                  v-model="row.key"
                  placeholder="Clé (ex: env)"
                  size="xs"
                  class="w-1/3 font-mono"
                />
                <UInput
                  v-model="row.value"
                  placeholder="Valeur (ex: production)"
                  size="xs"
                  class="flex-1 font-mono"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer"
                  @click="removeCustomParamRow(idx)"
                />
              </div>
            </div>
          </div>

          <!-- Boutons du formulaire -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
            <UButton
              variant="ghost"
              color="neutral"
              label="Annuler"
              @click="isModalOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              :loading="isSubmitting"
              :label="modalMode === 'create' ? 'Associer l\'intégration' : 'Enregistrer les modifications'"
            />
          </div>
        </form>
      </template>
    </UModal>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFetchItem } from "~/composables/api";
import { formatDateTime } from "~/utils/date";
import { getIdFromIri } from "~/utils/resource";
import { getEntrypoint } from "~/utils/config";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectintegration";
import type { Integration } from "~/types/integration";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: Project;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: Project): void;
}>();

const route = useRoute();
const router = useRouter();

const currentId = computed(() => {
  return props.id || (route.params.id ? String(route.params.id) : undefined);
});

function handleBack() {
  emit("back");
  router.push("/projects");
}

const item = ref<Project | undefined>(props.item);
const isLoading = ref(false);
const error = ref<string | undefined>(undefined);

// Intégrations associées au projet
const projectIntegrations = ref<ProjectIntegration[]>([]);
const isLoadingIntegrations = ref(false);
const integrationError = ref<string | undefined>(undefined);
const deletingId = ref<string | null>(null);

// Liste de toutes les intégrations système pour la sélection
const allIntegrations = ref<Integration[]>([]);

// Modale de création / édition
const isModalOpen = ref(false);
const modalMode = ref<"create" | "edit">("create");
const editingPi = ref<ProjectIntegration | null>(null);
const isSubmitting = ref(false);
const formError = ref<string | null>(null);

const formIntegrationIri = ref<string>("");
const formParamFields = ref<Record<string, string>>({
  repository: "",
  branch: "",
  project_key: "",
  job: "",
  project_id: "",
});
const customParamRows = ref<Array<{ key: string; value: string }>>([]);

// Helpers de formatage
function formatOrganisation(org: any): string {
  if (!org) return "Aucune";
  if (typeof org === "object") return org.name || org["@id"] || "Organisation";
  return String(org);
}

function getIntegration(pi: ProjectIntegration): Integration | null {
  if (!pi?.integration) return null;
  if (typeof pi.integration === "object") return pi.integration as Integration;
  return allIntegrations.value.find((i) => i["@id"] === pi.integration) || null;
}

function getIntegrationName(pi: ProjectIntegration): string {
  const integ = getIntegration(pi);
  return integ?.name || (typeof pi.integration === "string" ? pi.integration : "Intégration");
}

function getIntegrationType(pi: ProjectIntegration): string {
  const integ = getIntegration(pi);
  return (integ?.type || "unknown").toLowerCase();
}

function getIntegrationStatus(pi: ProjectIntegration): string {
  const integ = getIntegration(pi);
  return integ?.status || "unknown";
}

function getServerHost(pi: ProjectIntegration): string | null {
  const integ = getIntegration(pi);
  if (!integ?.server) return null;
  if (typeof integ.server === "object") {
    const s = integ.server as any;
    return s.host ? `${s.host}${s.port ? ':' + s.port : ''}` : s.name || null;
  }
  return null;
}

function hasParameters(pi: ProjectIntegration): boolean {
  if (!pi.parameters) return false;
  return Object.keys(pi.parameters).length > 0;
}

function getTypeIcon(type?: string): string {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "i-heroicons-code-bracket";
    case "jenkins":
      return "i-heroicons-arrow-path-rounded-square";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    default:
      return "i-heroicons-bolt";
  }
}

function getTypeBgClass(type?: string): string {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400";
    case "jenkins":
      return "bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400";
    case "mantis":
      return "bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400";
    case "sonarqube":
      return "bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400";
    default:
      return "bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400";
  }
}

function getTypeBadgeColor(type?: string): "warning" | "info" | "success" | "primary" | "neutral" {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "warning";
    case "jenkins":
      return "info";
    case "mantis":
      return "success";
    case "sonarqube":
      return "primary";
    default:
      return "neutral";
  }
}

function getStatusDotClass(status?: string): string {
  switch (status?.toLowerCase()) {
    case "healthy":
      return "bg-emerald-500";
    case "error":
      return "bg-red-500";
    case "warning":
      return "bg-amber-500";
    default:
      return "bg-neutral-400";
  }
}

function getStatusLabel(status?: string): string {
  switch (status?.toLowerCase()) {
    case "healthy":
      return "Opérationnel";
    case "error":
      return "Erreur";
    case "warning":
      return "Avertissement";
    default:
      return "Non vérifié";
  }
}

function isPrimaryParam(key: string, type?: string): boolean {
  switch (type?.toLowerCase()) {
    case "gitea":
      return key === "repository";
    case "sonarqube":
      return key === "project_key";
    case "jenkins":
      return key === "job" || key === "job_name";
    case "mantis":
      return key === "project_id";
    default:
      return false;
  }
}

function formatParamKey(key: string, type?: string): string {
  if (type === "gitea" && key === "repository") return "Dépôt Gitea (chemin)";
  if (type === "gitea" && key === "branch") return "Branche par défaut";
  if (type === "sonarqube" && key === "project_key") return "Clé de projet SonarQube";
  if (type === "jenkins" && (key === "job" || key === "job_name")) return "Nom du Job Jenkins";
  if (type === "mantis" && key === "project_id") return "Projet Mantis";
  return key;
}

function getExternalUrl(pi: ProjectIntegration): string | null {
  const integ = getIntegration(pi);
  if (!integ?.server || typeof integ.server !== "object") return null;

  const server = integ.server as any;
  if (!server.host) return null;

  const protocol = server.options?.protocol || "http";
  const port = server.port && !((protocol === "http" && server.port === 80) || (protocol === "https" && server.port === 443))
    ? `:${server.port}`
    : "";
  const baseUrl = `${protocol}://${server.host}${port}`;
  const params = pi.parameters || {};
  const type = integ.type?.toLowerCase();

  if (type === "gitea" && params.repository) {
    return `${baseUrl}/${params.repository}`;
  }
  if (type === "sonarqube" && params.project_key) {
    return `${baseUrl}/dashboard?id=${encodeURIComponent(params.project_key)}`;
  }
  if (type === "jenkins" && (params.job || params.job_name)) {
    return `${baseUrl}/job/${encodeURIComponent(params.job || params.job_name)}`;
  }
  if (type === "mantis" && params.project_id) {
    return `${baseUrl}/view_all_bug_page.php?project_id=${encodeURIComponent(params.project_id)}`;
  }

  return baseUrl;
}

// Chargement des données du projet
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

// Chargement des intégrations liées à ce projet
async function loadProjectIntegrations() {
  const projectIri = item.value?.["@id"] || (currentId.value ? `/api/projects/${currentId.value}` : null);
  if (!projectIri) return;

  isLoadingIntegrations.value = true;
  integrationError.value = undefined;

  try {
    const entrypoint = getEntrypoint();
    const res: any = await $fetch(`${entrypoint}/project_integrations`, {
      params: { project: projectIri },
      headers: { Accept: "application/ld+json" },
    });

    projectIntegrations.value = res?.member || res?.["hydra:member"] || [];
  } catch (err: any) {
    integrationError.value = err?.message || "Impossible de charger les intégrations du projet.";
  } finally {
    isLoadingIntegrations.value = false;
  }
}

// Chargement de l'ensemble des intégrations disponibles
async function loadAllIntegrations() {
  try {
    const entrypoint = getEntrypoint();
    const res: any = await $fetch(`${entrypoint}/integrations`, {
      headers: { Accept: "application/ld+json" },
    });
    allIntegrations.value = res?.member || res?.["hydra:member"] || [];
  } catch {
    // Non bloquant
  }
}

// Modale : Options d'intégrations pour le select
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

const selectedPiIntegrationName = computed(() => {
  if (!editingPi.value) return "";
  return getIntegrationName(editingPi.value);
});

function openLinkModal() {
  modalMode.value = "create";
  editingPi.value = null;
  formError.value = null;
  formIntegrationIri.value = allIntegrations.value[0]?.["@id"] || "";
  formParamFields.value = {
    repository: "",
    branch: "main",
    project_key: "",
    job: "",
    project_id: "",
  };
  customParamRows.value = [];
  isModalOpen.value = true;
}

function openEditModal(pi: ProjectIntegration) {
  modalMode.value = "edit";
  editingPi.value = pi;
  formError.value = null;
  formIntegrationIri.value = typeof pi.integration === "object" ? pi.integration?.["@id"] : (pi.integration || "");

  const params = pi.parameters || {};
  formParamFields.value = {
    repository: params.repository || "",
    branch: params.branch || "",
    project_key: params.project_key || "",
    job: params.job || params.job_name || "",
    project_id: params.project_id || "",
  };

  // Extraire les paramètres non standards
  const standardKeys = ["repository", "branch", "project_key", "job", "job_name", "project_id"];
  customParamRows.value = Object.entries(params)
    .filter(([k]) => !standardKeys.includes(k))
    .map(([key, value]) => ({ key, value: String(value) }));

  isModalOpen.value = true;
}

function addCustomParamRow() {
  customParamRows.value.push({ key: "", value: "" });
}

function removeCustomParamRow(idx: number) {
  customParamRows.value.splice(idx, 1);
}

// Soumission de la modale
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

  // Construction de l'objet de paramètres
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
    if (formParamFields.value.job?.trim()) {
      finalParameters.job = formParamFields.value.job.trim();
    }
  } else if (type === "mantis") {
    if (formParamFields.value.project_id?.trim()) {
      finalParameters.project_id = formParamFields.value.project_id.trim();
    }
  }

  // Paramètres personnalisés
  for (const row of customParamRows.value) {
    if (row.key?.trim()) {
      finalParameters[row.key.trim()] = row.value?.trim() || "";
    }
  }

  isSubmitting.value = true;
  formError.value = null;

  try {
    const entrypoint = getEntrypoint();

    if (modalMode.value === "create") {
      await $fetch(`${entrypoint}/project_integrations`, {
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
      await $fetch(`${entrypoint}${editingPi.value['@id']}`, {
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

// Dissocier une intégration
async function handleUnlink(pi: ProjectIntegration) {
  if (!pi["@id"]) return;
  const integName = getIntegrationName(pi);
  const ok = confirm(`Êtes-vous sûr de vouloir dissocier l'intégration "${integName}" de ce projet ?`);
  if (!ok) return;

  deletingId.value = pi["@id"];
  try {
    const entrypoint = getEntrypoint();
    await $fetch(`${entrypoint}${pi['@id']}`, {
      method: "DELETE",
    });
    await loadProjectIntegrations();
  } catch (err: any) {
    alert(err?.message || "Erreur lors de la suppression de la liaison.");
  } finally {
    deletingId.value = null;
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
