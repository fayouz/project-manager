<template>
  <div class="space-y-6">
    <!-- En-tête principal de l'onglet Déploiements -->
    <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-primary-500/10 dark:bg-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0 border border-primary-500/20">
            <UIcon name="i-heroicons-rocket-launch" class="w-7 h-7" />
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                Déploiements & Environnements Staging
              </h3>
              <UBadge color="primary" variant="subtle" size="xs">
                {{ stagings.length }} environnement{{ stagings.length > 1 ? 's' : '' }}
              </UBadge>
            </div>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
              Gérez les environnements de recette / staging rattachés aux serveurs de déploiement et accédez à leurs applications web.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <UButton
            color="neutral"
            variant="outline"
            size="sm"
            icon="i-heroicons-arrow-path"
            :loading="isLoading"
            label="Actualiser"
            @click="loadData"
          />
          <UButton
            color="neutral"
            variant="outline"
            size="sm"
            icon="i-heroicons-server-stack"
            label="Serveurs de déploiement"
            to="/deploymentservers"
          />
          <UButton
            color="primary"
            variant="solid"
            size="sm"
            icon="i-heroicons-plus"
            label="Nouveau Staging"
            @click="openCreateModal"
          />
        </div>
      </div>

      <!-- Alerte d'erreur éventuelle -->
      <UAlert
        v-if="errorMessage"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        title="Erreur"
        :description="errorMessage"
        close
        @close="errorMessage = null"
      />
    </UCard>

    <!-- État de chargement -->
    <div v-if="isLoading && stagings.length === 0" class="p-12 text-center text-neutral-500">
      <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin mx-auto mb-3 text-primary-500" />
      <p class="text-sm font-medium">Chargement des environnements de déploiement...</p>
    </div>

    <!-- État vide : aucun environnement configuré -->
    <div
      v-else-if="stagings.length === 0"
      class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl p-6"
    >
      <div class="w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center mx-auto mb-4">
        <UIcon name="i-heroicons-rocket-launch" class="w-8 h-8" />
      </div>
      <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
        Aucun environnement de staging pour ce projet
      </h3>
      <p class="text-sm text-neutral-500 dark:text-neutral-400 max-w-md mx-auto mt-1">
        Définissez vos environnements (recette, staging, préproduction) et associez-les à un serveur de déploiement pour accéder à vos URLs d'application.
      </p>
      <div class="mt-6 flex items-center justify-center gap-3">
        <UButton
          color="primary"
          icon="i-heroicons-plus"
          label="Créer un environnement de staging"
          @click="openCreateModal"
        />
      </div>
    </div>

    <!-- Grille des environnements Staging -->
    <div v-else class="space-y-6">
      <!-- KPIs rapides -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Environnements</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                {{ stagings.length }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
              <UIcon name="i-heroicons-squares-2x2" class="w-6 h-6" />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Environnements Actifs</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                {{ activeStagingsCount }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <UIcon name="i-heroicons-check-circle" class="w-6 h-6" />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Serveurs Associés</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                {{ distinctServersCount }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <UIcon name="i-heroicons-server-stack" class="w-6 h-6" />
            </div>
          </div>
        </UCard>
      </div>

      <!-- Liste des cartes Staging -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <UCard
          v-for="staging in stagings"
          :key="staging['@id'] || staging.id"
          class="flex flex-col justify-between hover:border-primary-500/40 transition shadow-sm"
          :ui="{ body: 'p-5 sm:p-6 space-y-4 flex-1 flex flex-col justify-between' }"
        >
          <div class="space-y-3">
            <!-- Haut de carte : Titre et Badges -->
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                    {{ staging.name }}
                  </h4>
                  <UBadge
                    :color="getEnvironmentBadgeColor(staging.environment)"
                    variant="subtle"
                    size="xs"
                    class="uppercase text-[10px] font-semibold"
                  >
                    {{ staging.environment || 'staging' }}
                  </UBadge>
                </div>
                <p v-if="staging.description" class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 line-clamp-2">
                  {{ staging.description }}
                </p>
              </div>

              <!-- Statut -->
              <UBadge
                :color="getStatusBadgeColor(staging.status)"
                variant="soft"
                size="sm"
                class="flex items-center gap-1.5 shrink-0"
              >
                <span class="w-2 h-2 rounded-full" :class="getStatusDotClass(staging.status)"></span>
                {{ staging.status || 'actif' }}
              </UBadge>
            </div>

            <!-- Détails de la branche -->
            <div v-if="staging.branch" class="flex items-center gap-1.5 text-xs text-neutral-600 dark:text-neutral-400 font-mono bg-neutral-100 dark:bg-neutral-800/60 px-2.5 py-1 rounded-lg w-fit">
              <UIcon name="i-heroicons-hashtag" class="w-3.5 h-3.5 text-primary-500" />
              <span>Branche : {{ staging.branch }}</span>
            </div>

            <!-- Bloc Serveur de déploiement & Webserver URL -->
            <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200/70 dark:border-neutral-700/60 space-y-2.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-neutral-500 flex items-center gap-1.5">
                  <UIcon name="i-heroicons-server" class="w-4 h-4 text-indigo-500" />
                  Serveur de déploiement
                </span>
                <span class="font-semibold text-neutral-900 dark:text-neutral-100">
                  {{ getServerName(staging) }}
                </span>
              </div>

              <div v-if="getServerHost(staging)" class="flex items-center justify-between text-xs">
                <span class="text-neutral-500">Hôte / Port</span>
                <span class="font-mono text-neutral-700 dark:text-neutral-300">
                  {{ getServerHost(staging) }}
                </span>
              </div>

              <!-- Webserver URL -->
              <div class="pt-2 border-t border-neutral-200/60 dark:border-neutral-700/40">
                <div class="flex items-center justify-between gap-2">
                  <div class="min-w-0">
                    <p class="text-[11px] font-medium text-neutral-500">URL du serveur web</p>
                    <p v-if="getWebserverUrl(staging)" class="text-xs font-semibold text-primary-600 dark:text-primary-400 truncate">
                      {{ getWebserverUrl(staging) }}
                    </p>
                    <p v-else class="text-xs text-neutral-400 italic">
                      Non configurée
                    </p>
                  </div>

                  <UButton
                    v-if="getWebserverUrl(staging)"
                    :to="getWebserverUrl(staging)!"
                    target="_blank"
                    size="xs"
                    color="primary"
                    variant="soft"
                    icon="i-heroicons-arrow-top-right-on-square"
                    label="Ouvrir le site"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Actions en pied de carte -->
          <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-2 mt-4">
            <span class="text-[11px] text-neutral-400 font-mono">
              ID #{{ staging.id || getIdFromIri(staging['@id']) }}
            </span>
            <div class="flex items-center gap-2">
              <UButton
                size="xs"
                variant="ghost"
                color="neutral"
                icon="i-heroicons-pencil-square"
                label="Modifier"
                @click="openEditModal(staging)"
              />
              <UButton
                size="xs"
                variant="ghost"
                color="error"
                icon="i-heroicons-trash"
                aria-label="Supprimer"
                @click="deleteStaging(staging)"
              />
            </div>
          </div>
        </UCard>
      </div>
    </div>

    <!-- Modale de Création / Modification d'un Staging -->
    <UModal
      v-model:open="isStagingModalOpen"
      :title="modalMode === 'create' ? 'Ajouter un environnement Staging' : 'Modifier le Staging'"
      :description="modalMode === 'create' ? 'Rattachez un nouvel environnement de déploiement à ce projet.' : 'Mettez à jour les paramètres de cet environnement.'"
    >
      <template #body>
        <form class="space-y-4" @submit.prevent="saveStaging">
          <UAlert
            v-if="modalError"
            color="error"
            :title="modalError"
            icon="i-heroicons-exclamation-triangle"
            size="sm"
          />

          <!-- Nom -->
          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Nom de l'environnement <span class="text-red-500">*</span>
            </label>
            <UInput
              v-model="formData.name"
              placeholder="Ex: Staging Principal, Recette Client, Preprod"
              required
              class="w-full"
            />
          </div>

          <!-- Environnement & Statut -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Type d'environnement
              </label>
              <USelect
                v-model="formData.environment"
                :items="environmentOptions"
                class="w-full"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Statut
              </label>
              <USelect
                v-model="formData.status"
                :items="statusOptions"
                class="w-full"
              />
            </div>
          </div>

          <!-- Branche Git -->
          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Branche Git associée
            </label>
            <UInput
              v-model="formData.branch"
              placeholder="Ex: main, develop, release/v1.0"
              icon="i-heroicons-hashtag"
              class="w-full"
            />
          </div>

          <!-- Serveur de déploiement -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Serveur de déploiement
              </label>
              <UButton
                type="button"
                variant="link"
                color="primary"
                size="xs"
                icon="i-heroicons-plus"
                label="Créer un serveur"
                @click="openCreateServerModal"
              />
            </div>

            <USelect
              v-model="formData.deploymentServer"
              :items="serverSelectItems"
              placeholder="Sélectionner un serveur de déploiement"
              class="w-full"
            />
            <p v-if="selectedServerPreview" class="text-xs text-neutral-500 flex items-center gap-1.5 mt-1">
              <UIcon name="i-heroicons-globe-alt" class="w-3.5 h-3.5 text-primary-500" />
              <span>URL serveur web : <strong class="text-neutral-800 dark:text-neutral-200">{{ selectedServerPreview.webserverUrl || 'Non définie' }}</strong></span>
            </p>
          </div>

          <!-- Description -->
          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Description (optionnel)
            </label>
            <UTextarea
              v-model="formData.description"
              placeholder="Notes, accès, informations spécifiques sur cet environnement..."
              rows="3"
              class="w-full"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-neutral-800">
            <UButton
              type="button"
              variant="ghost"
              color="neutral"
              label="Annuler"
              @click="isStagingModalOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              :loading="isSubmitting"
              icon="i-heroicons-check"
              label="Enregistrer"
            />
          </div>
        </form>
      </template>
    </UModal>

    <!-- Modale rapide de création d'un DeploymentServer -->
    <UModal
      v-model:open="isServerModalOpen"
      title="Nouveau serveur de déploiement"
      description="Créez un serveur de déploiement avec son URL web associée."
    >
      <template #body>
        <form class="space-y-4" @submit.prevent="saveServer">
          <UAlert
            v-if="serverModalError"
            color="error"
            :title="serverModalError"
            icon="i-heroicons-exclamation-triangle"
            size="sm"
          />

          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Nom du serveur <span class="text-red-500">*</span>
            </label>
            <UInput
              v-model="serverFormData.name"
              placeholder="Ex: Serveur Staging Web 01, Cluster Kubernetes"
              required
              class="w-full"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              URL du serveur web (webserverUrl) <span class="text-red-500">*</span>
            </label>
            <UInput
              v-model="serverFormData.webserverUrl"
              placeholder="Ex: https://staging.example.com"
              icon="i-heroicons-globe-alt"
              required
              class="w-full"
            />
            <p class="text-[11px] text-neutral-400">
              URL publique ou interne permettant d'accéder aux applications déployées sur ce serveur.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Hôte (IP / Nom de domaine)
              </label>
              <UInput
                v-model="serverFormData.host"
                placeholder="Ex: 192.168.1.50 ou vps.example.com"
                class="w-full"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                Port
              </label>
              <UInput
                v-model.number="serverFormData.port"
                type="number"
                placeholder="Ex: 443"
                class="w-full"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Description (optionnel)
            </label>
            <UTextarea
              v-model="serverFormData.description"
              placeholder="Configuration machine, hébergeur..."
              rows="2"
              class="w-full"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-neutral-800">
            <UButton
              type="button"
              variant="ghost"
              color="neutral"
              label="Annuler"
              @click="isServerModalOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              :loading="isSubmittingServer"
              icon="i-heroicons-check"
              label="Créer le serveur"
            />
          </div>
        </form>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import type { Project } from "~/types/project";
import type { Staging } from "~/types/staging";
import type { DeploymentServer } from "~/types/deploymentserver";
import { resolveApiUrl } from "~/utils/config";
import { getIdFromIri } from "~/utils/resource";

const props = defineProps<{
  project: Project;
}>();

const emit = defineEmits<{
  (e: "updated"): void;
}>();

const isLoading = ref(false);
const errorMessage = ref<string | null>(null);
const stagings = ref<Staging[]>([]);
const deploymentServers = ref<DeploymentServer[]>([]);

// Options d'environnements et de statuts
const environmentOptions = [
  { label: "Staging (Recette)", value: "staging" },
  { label: "Préproduction", value: "preprod" },
  { label: "Développement", value: "dev" },
  { label: "Production", value: "production" },
  { label: "Review / PR", value: "review" },
];

const statusOptions = [
  { label: "Actif / Opérationnel", value: "active" },
  { label: "Déployé", value: "deployed" },
  { label: "En cours de déploiement", value: "in_progress" },
  { label: "En attente", value: "pending" },
  { label: "Arrêté / Inactif", value: "stopped" },
];

// Modale Staging
const isStagingModalOpen = ref(false);
const modalMode = ref<"create" | "edit">("create");
const editingStaging = ref<Staging | null>(null);
const isSubmitting = ref(false);
const modalError = ref<string | null>(null);

const formData = ref({
  name: "",
  environment: "staging",
  status: "active",
  branch: "main",
  deploymentServer: "",
  description: "",
});

// Modale Serveur de déploiement rapide
const isServerModalOpen = ref(false);
const isSubmittingServer = ref(false);
const serverModalError = ref<string | null>(null);

const serverFormData = ref({
  name: "",
  webserverUrl: "",
  host: "",
  port: 443 as number | undefined,
  description: "",
});

// Éléments du sélecteur de serveur
const serverSelectItems = computed(() => {
  return [
    { label: "Aucun serveur associé", value: "" },
    ...deploymentServers.value.map((s) => ({
      label: `${s.name || 'Serveur'} (${s.webserverUrl || s.host || 'sans URL'})`,
      value: s["@id"] || `/api/deployment_servers/${s.id}`,
    })),
  ];
});

const selectedServerPreview = computed(() => {
  if (!formData.value.deploymentServer) return null;
  return deploymentServers.value.find(
    (s) => s["@id"] === formData.value.deploymentServer || `/api/deployment_servers/${s.id}` === formData.value.deploymentServer
  ) || null;
});

// KPIs
const activeStagingsCount = computed(() => {
  return stagings.value.filter(
    (s) => s.status === "active" || s.status === "deployed"
  ).length;
});

const distinctServersCount = computed(() => {
  const set = new Set();
  stagings.value.forEach((s) => {
    const sId = typeof s.deploymentServer === "object" ? s.deploymentServer?.["@id"] : s.deploymentServer;
    if (sId) set.add(sId);
  });
  return set.size;
});

// Badges colors
function getEnvironmentBadgeColor(env?: string): "primary" | "secondary" | "success" | "warning" | "error" | "info" | "neutral" {
  switch (env?.toLowerCase()) {
    case "production":
    case "prod":
      return "error";
    case "preprod":
      return "warning";
    case "staging":
      return "primary";
    case "dev":
      return "info";
    default:
      return "neutral";
  }
}

function getStatusBadgeColor(status?: string): "primary" | "secondary" | "success" | "warning" | "error" | "info" | "neutral" {
  switch (status?.toLowerCase()) {
    case "active":
    case "deployed":
      return "success";
    case "in_progress":
      return "warning";
    case "stopped":
      return "error";
    default:
      return "neutral";
  }
}

function getStatusDotClass(status?: string): string {
  switch (status?.toLowerCase()) {
    case "active":
    case "deployed":
      return "bg-emerald-500 animate-pulse";
    case "in_progress":
      return "bg-amber-500 animate-spin";
    case "stopped":
      return "bg-red-500";
    default:
      return "bg-neutral-400";
  }
}

// Helpers Serveur
function getServer(staging: Staging): DeploymentServer | null {
  if (!staging.deploymentServer) return null;
  if (typeof staging.deploymentServer === "object" && staging.deploymentServer.name) {
    return staging.deploymentServer as DeploymentServer;
  }
  const serverIri = typeof staging.deploymentServer === "string" ? staging.deploymentServer : staging.deploymentServer?.["@id"];
  return deploymentServers.value.find((s) => s["@id"] === serverIri) || null;
}

function getServerName(staging: Staging): string {
  const server = getServer(staging);
  return server?.name || (staging.deploymentServer ? "Serveur lié" : "Non assigné");
}

function getServerHost(staging: Staging): string | null {
  const server = getServer(staging);
  if (!server) return null;
  if (server.host && server.port) {
    return `${server.host}:${server.port}`;
  }
  return server.host || (server.port ? `Port ${server.port}` : null);
}

function getWebserverUrl(staging: Staging): string | null {
  const server = getServer(staging);
  return server?.webserverUrl || null;
}

// Chargement des données
async function loadData() {
  const projectIri = props.project?.["@id"] || (props.project?.id ? `/api/projects/${props.project.id}` : null);
  if (!projectIri) return;

  isLoading.value = true;
  errorMessage.value = null;

  try {
    const [stagingsRes, serversRes]: [any, any] = await Promise.all([
      $fetch(resolveApiUrl("/stagings"), {
        params: { project: projectIri },
        headers: { Accept: "application/ld+json" },
      }),
      $fetch(resolveApiUrl("/deployment_servers"), {
        headers: { Accept: "application/ld+json" },
      }),
    ]);

    stagings.value = stagingsRes?.member || stagingsRes?.["hydra:member"] || [];
    deploymentServers.value = serversRes?.member || serversRes?.["hydra:member"] || [];
  } catch (err: any) {
    errorMessage.value = err?.message || "Erreur lors du chargement des environnements de déploiement.";
  } finally {
    isLoading.value = false;
  }
}

function openCreateModal() {
  modalMode.value = "create";
  editingStaging.value = null;
  modalError.value = null;
  formData.value = {
    name: "",
    environment: "staging",
    status: "active",
    branch: "main",
    deploymentServer: deploymentServers.value.length > 0 ? (deploymentServers.value[0]["@id"] || "") : "",
    description: "",
  };
  isStagingModalOpen.value = true;
}

function openEditModal(staging: Staging) {
  modalMode.value = "edit";
  editingStaging.value = staging;
  modalError.value = null;

  const serverIri = typeof staging.deploymentServer === "object"
    ? staging.deploymentServer?.["@id"]
    : staging.deploymentServer;

  formData.value = {
    name: staging.name || "",
    environment: staging.environment || "staging",
    status: staging.status || "active",
    branch: staging.branch || "",
    deploymentServer: serverIri || "",
    description: staging.description || "",
  };
  isStagingModalOpen.value = true;
}

async function saveStaging() {
  const projectIri = props.project?.["@id"] || (props.project?.id ? `/api/projects/${props.project.id}` : null);
  if (!projectIri) return;

  isSubmitting.value = true;
  modalError.value = null;

  const payload: any = {
    name: formData.value.name,
    project: projectIri,
    environment: formData.value.environment,
    status: formData.value.status,
    branch: formData.value.branch,
    description: formData.value.description,
    deploymentServer: formData.value.deploymentServer || null,
  };

  try {
    if (modalMode.value === "create") {
      await $fetch(resolveApiUrl("/stagings"), {
        method: "POST",
        headers: {
          "Content-Type": "application/ld+json",
          Accept: "application/ld+json",
        },
        body: payload,
      });
    } else if (editingStaging.value?.["@id"]) {
      await $fetch(resolveApiUrl(editingStaging.value["@id"]), {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          Accept: "application/ld+json",
        },
        body: payload,
      });
    }

    isStagingModalOpen.value = false;
    await loadData();
    emit("updated");
  } catch (err: any) {
    modalError.value = err?.data?.["hydra:description"] || err?.message || "Erreur lors de l'enregistrement du Staging.";
  } finally {
    isSubmitting.value = false;
  }
}

async function deleteStaging(staging: Staging) {
  if (!staging["@id"]) return;
  const ok = confirm(`Êtes-vous sûr de vouloir supprimer l'environnement "${staging.name}" ?`);
  if (!ok) return;

  try {
    await $fetch(resolveApiUrl(staging["@id"]), {
      method: "DELETE",
    });
    await loadData();
    emit("updated");
  } catch (err: any) {
    alert(err?.message || "Erreur lors de la suppression du Staging.");
  }
}

function openCreateServerModal() {
  serverModalError.value = null;
  serverFormData.value = {
    name: "",
    webserverUrl: "https://",
    host: "",
    port: 443,
    description: "",
  };
  isServerModalOpen.value = true;
}

async function saveServer() {
  isSubmittingServer.value = true;
  serverModalError.value = null;

  try {
    const res: any = await $fetch(resolveApiUrl("/deployment_servers"), {
      method: "POST",
      headers: {
        "Content-Type": "application/ld+json",
        Accept: "application/ld+json",
      },
      body: {
        name: serverFormData.value.name,
        webserverUrl: serverFormData.value.webserverUrl,
        host: serverFormData.value.host || null,
        port: serverFormData.value.port ? Number(serverFormData.value.port) : null,
        description: serverFormData.value.description || null,
      },
    });

    const newServerIri = res?.["@id"] || (res?.id ? `/api/deployment_servers/${res.id}` : null);
    await loadData();

    if (newServerIri) {
      formData.value.deploymentServer = newServerIri;
    }

    isServerModalOpen.value = false;
  } catch (err: any) {
    serverModalError.value = err?.data?.["hydra:description"] || err?.message || "Erreur lors de la création du serveur.";
  } finally {
    isSubmittingServer.value = false;
  }
}

onMounted(() => {
  loadData();
});

watch(
  () => props.project?.["@id"],
  (newVal) => {
    if (newVal) loadData();
  }
);
</script>
