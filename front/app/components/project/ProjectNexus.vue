<template>
  <div class="space-y-6">
    <!-- Cas 1 : Nexus est configuré pour ce projet -->
    <div v-if="projectIntegration" class="space-y-6">
      <!-- En-tête Nexus Repository & Dépôt d'artefacts -->
      <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-500/10 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 border border-teal-500/20">
              <UIcon name="i-heroicons-cube" class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                  {{ repositoryName || 'Nexus Repository' }}
                </h3>
                <UBadge color="info" variant="subtle" size="xs">
                  Nexus Repository
                </UBadge>
                <UBadge
                  v-if="repositoryFormat"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-[11px]"
                >
                  {{ repositoryFormat }}
                </UBadge>
                <UBadge
                  v-if="repositoryType"
                  color="neutral"
                  variant="subtle"
                  size="xs"
                >
                  {{ repositoryType }}
                </UBadge>
                <UBadge
                  :color="isOnline ? 'success' : 'error'"
                  variant="soft"
                  size="xs"
                  class="flex items-center gap-1"
                >
                  <span class="size-1.5 rounded-full" :class="isOnline ? 'bg-emerald-500' : 'bg-red-500'" />
                  {{ isOnline ? 'En ligne' : 'Indisponible' }}
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-3 flex-wrap">
                <span class="flex items-center gap-1 font-medium text-neutral-700 dark:text-neutral-300">
                  <UIcon name="i-heroicons-archive-box" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" />
                  {{ componentsList.length }} composant(s)
                </span>
                <span class="flex items-center gap-1 text-neutral-600 dark:text-neutral-400">
                  <UIcon name="i-heroicons-document-arrow-down" class="w-3.5 h-3.5" />
                  {{ assetsList.length }} artefact(s) / actif(s)
                </span>
                <span v-if="groupFilter" class="flex items-center gap-1 font-mono text-[11px] text-neutral-400">
                  Filtre groupe : {{ groupFilter }}
                </span>
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
              @click="refreshData"
            />
            <UButton
              v-if="nexusExternalUrl"
              :to="nexusExternalUrl"
              target="_blank"
              color="primary"
              variant="soft"
              size="sm"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir dans Nexus"
            />
            <UButton
              color="neutral"
              variant="outline"
              size="sm"
              icon="i-heroicons-cog-6-tooth"
              label="Paramètres de l'intégration"
              @click="emit('configure')"
            />
          </div>
        </div>

        <!-- Alerte d'erreur éventuelle -->
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-heroicons-exclamation-triangle"
          title="Erreur de synchronisation Nexus"
          :description="errorMessage"
          close
          @close="liveDataStore.clear(projectIntegration?.id)"
        />
      </UCard>

      <!-- État de chargement initial -->
      <div v-if="isLoading && !liveData" class="space-y-4">
        <div class="p-8 text-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-teal-500 animate-spin mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
            Chargement des artefacts depuis Nexus...
          </p>
          <p class="text-xs text-neutral-500 mt-1">
            Interrogation de Nexus Repository pour synchroniser les composants et fichiers téléchargeables.
          </p>
        </div>
      </div>

      <div v-else class="space-y-6">
        <!-- 4 Cartes KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Dépôt cible</p>
                <p class="text-base font-bold mt-1 text-neutral-900 dark:text-neutral-100 truncate" :title="repositoryName">
                  {{ repositoryName || 'Tous les dépôts' }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">
                  Format : {{ repositoryFormat || 'Auto' }}
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-cube" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Composants</p>
                <p class="text-2xl font-bold mt-1 text-teal-600 dark:text-teal-400">
                  {{ componentsList.length }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">
                  Packages publiés
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-archive-box" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Actifs & Artefacts</p>
                <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                  {{ assetsList.length }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">
                  Fichiers binaires
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-document-arrow-down" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Dépôts disponibles</p>
                <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                  {{ repositoriesList.length }}
                </p>
                <p class="text-xs text-neutral-400 mt-0.5">
                  Sur ce serveur Nexus
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-server-stack" class="w-5 h-5" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Section de navigation interne par onglets : Composants / Actifs / Dépôts -->
        <UCard :ui="{ body: 'p-5 sm:p-6 space-y-5' }">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-100 dark:border-neutral-800 pb-4">
            <div class="flex items-center gap-2">
              <UButton
                :variant="viewTab === 'components' ? 'solid' : 'ghost'"
                :color="viewTab === 'components' ? 'primary' : 'neutral'"
                size="sm"
                icon="i-heroicons-archive-box"
                label="Composants"
                @click="viewTab = 'components'"
              />
              <UButton
                :variant="viewTab === 'assets' ? 'solid' : 'ghost'"
                :color="viewTab === 'assets' ? 'primary' : 'neutral'"
                size="sm"
                icon="i-heroicons-document-arrow-down"
                label="Artefacts & Fichiers"
                @click="viewTab = 'assets'"
              />
              <UButton
                :variant="viewTab === 'repositories' ? 'solid' : 'ghost'"
                :color="viewTab === 'repositories' ? 'primary' : 'neutral'"
                size="sm"
                icon="i-heroicons-server-stack"
                label="Dépôts Nexus"
                @click="viewTab = 'repositories'"
              />
            </div>

            <div class="w-full sm:w-64">
              <UInput
                v-model="searchQuery"
                icon="i-heroicons-magnifying-glass"
                size="sm"
                placeholder="Filtrer..."
                class="w-full"
              />
            </div>
          </div>

          <!-- 1. Onglet Composants -->
          <div v-show="viewTab === 'components'" class="space-y-4">
            <div v-if="filteredComponents.length === 0" class="text-center py-10 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
              <UIcon name="i-heroicons-archive-box" class="mx-auto size-10 text-neutral-400 mb-2" />
              <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ searchQuery ? 'Aucun composant ne correspond à votre recherche' : 'Aucun composant trouvé' }}
              </p>
              <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                {{ searchQuery ? 'Modifiez le terme de recherche ou réinitialisez le filtre.' : 'Aucun composant n\'a été détecté dans le dépôt configuré.' }}
              </p>
            </div>

            <div v-else class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-800">
              <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800 text-xs text-left">
                <thead class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-500 font-semibold">
                  <tr>
                    <th class="py-3 px-4">Nom</th>
                    <th class="py-3 px-4">Groupe</th>
                    <th class="py-3 px-4">Version</th>
                    <th class="py-3 px-4">Format</th>
                    <th class="py-3 px-4">Actifs liés</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800/60 font-medium">
                  <tr v-for="comp in filteredComponents" :key="comp.id" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                    <td class="py-3 px-4 text-neutral-900 dark:text-neutral-100 font-bold">
                      <div class="flex items-center gap-2">
                        <UIcon name="i-heroicons-cube" class="w-4 h-4 text-teal-500 shrink-0" />
                        <span>{{ comp.name }}</span>
                      </div>
                    </td>
                    <td class="py-3 px-4 font-mono text-neutral-600 dark:text-neutral-300">
                      {{ comp.group || '-' }}
                    </td>
                    <td class="py-3 px-4">
                      <UBadge color="primary" variant="subtle" size="xs">
                        {{ comp.version || 'latest' }}
                      </UBadge>
                    </td>
                    <td class="py-3 px-4">
                      <UBadge color="neutral" variant="outline" size="xs">
                        {{ comp.format }}
                      </UBadge>
                    </td>
                    <td class="py-3 px-4 text-neutral-500">
                      {{ comp.assets?.length || 0 }} fichier(s)
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2. Onglet Actifs & Artefacts -->
          <div v-show="viewTab === 'assets'" class="space-y-4">
            <div v-if="filteredAssets.length === 0" class="text-center py-10 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
              <UIcon name="i-heroicons-document-arrow-down" class="mx-auto size-10 text-neutral-400 mb-2" />
              <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ searchQuery ? 'Aucun artefact ne correspond à votre recherche' : 'Aucun artefact trouvé' }}
              </p>
              <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                Les artefacts téléchargeables apparaîtront ici lorsqu'ils seront publiés dans le dépôt Nexus.
              </p>
            </div>

            <div v-else class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-800">
              <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800 text-xs text-left">
                <thead class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-500 font-semibold">
                  <tr>
                    <th class="py-3 px-4">Chemin</th>
                    <th class="py-3 px-4">Taille</th>
                    <th class="py-3 px-4">Format</th>
                    <th class="py-3 px-4">Type MIME</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4 text-right">Téléchargement</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800/60 font-medium">
                  <tr v-for="asset in filteredAssets" :key="asset.id" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                    <td class="py-3 px-4 font-mono text-neutral-900 dark:text-neutral-100">
                      {{ asset.path }}
                    </td>
                    <td class="py-3 px-4 text-neutral-600 dark:text-neutral-300">
                      {{ formatFileSize(asset.fileSize) }}
                    </td>
                    <td class="py-3 px-4">
                      <UBadge color="neutral" variant="outline" size="xs">
                        {{ asset.format }}
                      </UBadge>
                    </td>
                    <td class="py-3 px-4 text-neutral-500">
                      {{ asset.contentType || '-' }}
                    </td>
                    <td class="py-3 px-4 text-neutral-400">
                      {{ formatDate(asset.lastModified) }}
                    </td>
                    <td class="py-3 px-4 text-right">
                      <UButton
                        v-if="asset.downloadUrl"
                        :to="asset.downloadUrl"
                        target="_blank"
                        size="xs"
                        variant="soft"
                        color="primary"
                        icon="i-heroicons-arrow-down-tray"
                        label="Télécharger"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 3. Onglet Dépôts Nexus de l'instance -->
          <div v-show="viewTab === 'repositories'" class="space-y-4">
            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-800">
              <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800 text-xs text-left">
                <thead class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-500 font-semibold">
                  <tr>
                    <th class="py-3 px-4">Nom du dépôt</th>
                    <th class="py-3 px-4">Format</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Statut</th>
                    <th class="py-3 px-4 text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800/60 font-medium">
                  <tr v-for="repo in filteredRepositories" :key="repo.name" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                    <td class="py-3 px-4 font-bold text-neutral-900 dark:text-neutral-100">
                      <div class="flex items-center gap-2">
                        <UIcon name="i-heroicons-server" class="w-4 h-4 text-teal-600 shrink-0" />
                        <span>{{ repo.name }}</span>
                        <UBadge v-if="repo.name === repositoryName" color="primary" variant="subtle" size="xs">
                          Actuel
                        </UBadge>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <UBadge color="neutral" variant="outline" size="xs">
                        {{ repo.format }}
                      </UBadge>
                    </td>
                    <td class="py-3 px-4 text-neutral-600 dark:text-neutral-300">
                      {{ repo.type }}
                    </td>
                    <td class="py-3 px-4">
                      <UBadge :color="repo.online ? 'success' : 'error'" variant="subtle" size="xs">
                        {{ repo.online ? 'En ligne' : 'Hors ligne' }}
                      </UBadge>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <UButton
                        v-if="repo.url"
                        :to="repo.url"
                        target="_blank"
                        size="xs"
                        variant="ghost"
                        color="neutral"
                        icon="i-heroicons-arrow-top-right-on-square"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </UCard>
      </div>
    </div>

    <!-- Cas 2 : Nexus n'est pas configuré pour ce projet -->
    <div v-else class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl p-6">
      <div class="w-14 h-14 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center mx-auto mb-4 border border-teal-500/20">
        <UIcon name="i-heroicons-cube" class="w-8 h-8" />
      </div>
      <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
        Nexus Repository n'est pas associé à ce projet
      </h3>
      <p class="mt-1 text-sm text-neutral-500 max-w-md mx-auto">
        Liez une instance Nexus pour consulter les artefacts compilés, packages de dépendances et dépôts associés à ce projet.
      </p>
      <div class="mt-6">
        <UButton
          color="primary"
          icon="i-heroicons-plus"
          label="Configurer l'intégration Nexus"
          @click="emit('configure')"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { NexusLiveData, NexusComponent, NexusAsset, NexusRepository } from "~/types/liveData";
import { useProjectIntegrationLiveDataStore } from "~/stores/projectIntegration/liveData";
import { getExternalUrl } from "~/utils/integration";

const props = defineProps<{
  project: Project;
  projectIntegration?: ProjectIntegration | null;
}>();

const emit = defineEmits<{
  (e: "configure"): void;
}>();

const liveDataStore = useProjectIntegrationLiveDataStore();
const viewTab = ref<"components" | "assets" | "repositories">("components");
const searchQuery = ref("");

const piId = computed(() => props.projectIntegration?.id);

const liveData = computed<NexusLiveData | null>(() => {
  if (!piId.value) return null;
  return liveDataStore.getData<NexusLiveData>(piId.value);
});

const isLoading = computed(() => {
  if (!piId.value) return false;
  return liveDataStore.isLoading(piId.value);
});

const errorMessage = computed(() => {
  if (!piId.value) return null;
  return liveDataStore.getError(piId.value);
});

const repositoryName = computed(() => {
  return (
    liveData.value?.repository ||
    props.projectIntegration?.parameters?.repository ||
    ""
  );
});

const groupFilter = computed(() => {
  return (
    liveData.value?.group ||
    props.projectIntegration?.parameters?.group ||
    ""
  );
});

const repositoryFormat = computed(() => liveData.value?.format || "");
const repositoryType = computed(() => liveData.value?.type || "");
const isOnline = computed(() => liveData.value?.online ?? true);

const nexusExternalUrl = computed(() => {
  if (liveData.value?.url) return liveData.value.url;
  if (props.projectIntegration) return getExternalUrl(props.projectIntegration);
  return null;
});

const componentsList = computed<NexusComponent[]>(() => {
  return liveData.value?.components || [];
});

const assetsList = computed<NexusAsset[]>(() => {
  return liveData.value?.assets || [];
});

const repositoriesList = computed<NexusRepository[]>(() => {
  return liveData.value?.repositories || [];
});

const filteredComponents = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return componentsList.value;
  return componentsList.value.filter((c) => {
    return (
      c.name.toLowerCase().includes(q) ||
      c.group.toLowerCase().includes(q) ||
      c.version.toLowerCase().includes(q) ||
      c.format.toLowerCase().includes(q)
    );
  });
});

const filteredAssets = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return assetsList.value;
  return assetsList.value.filter((a) => {
    return (
      a.path.toLowerCase().includes(q) ||
      a.format.toLowerCase().includes(q) ||
      (a.contentType && a.contentType.toLowerCase().includes(q))
    );
  });
});

const filteredRepositories = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return repositoriesList.value;
  return repositoriesList.value.filter((r) => {
    return (
      r.name.toLowerCase().includes(q) ||
      r.format.toLowerCase().includes(q) ||
      r.type.toLowerCase().includes(q)
    );
  });
});

function formatFileSize(bytes?: number | null): string {
  if (!bytes || bytes <= 0) return "-";
  const units = ["o", "Ko", "Mo", "Go"];
  let val = bytes;
  let idx = 0;
  while (val >= 1024 && idx < units.length - 1) {
    val /= 1024;
    idx++;
  }
  return `${val.toFixed(1)} ${units[idx]}`;
}

function formatDate(dateStr?: string): string {
  if (!dateStr) return "-";
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return dateStr;
  }
}

async function loadData(force = false) {
  if (!piId.value) return;
  await liveDataStore.fetchLiveData(piId.value, force);
}

function refreshData() {
  loadData(true);
}

watch(
  piId,
  (newId) => {
    if (newId) {
      loadData(false);
    }
  },
  { immediate: true }
);
</script>
