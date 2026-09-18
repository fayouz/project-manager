<template>
  <div class="space-y-6">
    <!-- Cas 1 : Jenkins est configuré pour ce projet -->
    <div v-if="projectIntegration" class="space-y-6">
      <!-- En-tête Jenkins CI & Intégration continue -->
      <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-500/10 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 border border-sky-500/20">
              <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                  {{ displayFolderName || 'Pipelines Jenkins CI' }}
                </h3>
                <UBadge color="info" variant="subtle" size="xs">
                  Jenkins CI
                </UBadge>
                <UBadge
                  v-if="folderPath"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-[11px]"
                >
                  {{ folderPath }}
                </UBadge>
                <UBadge color="neutral" variant="subtle" size="xs">
                  {{ jobsList.length }} job(s) référencé(s)
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-3 flex-wrap">
                <span v-if="stats.building > 0" class="flex items-center gap-1 font-semibold text-primary-500 animate-pulse">
                  <UIcon name="i-heroicons-arrow-path" class="w-3.5 h-3.5 animate-spin" />
                  {{ stats.building }} build(s) en cours
                </span>
                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                  <UIcon name="i-heroicons-check-circle" class="w-3.5 h-3.5" />
                  {{ stats.success }} job(s) au vert
                </span>
                <span v-if="stats.failure > 0" class="flex items-center gap-1 text-red-600 dark:text-red-400 font-semibold">
                  <UIcon name="i-heroicons-x-circle" class="w-3.5 h-3.5" />
                  {{ stats.failure }} en échec
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
              v-if="folderExternalUrl"
              :to="folderExternalUrl"
              target="_blank"
              color="primary"
              variant="soft"
              size="sm"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir le dossier Jenkins"
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
          title="Erreur de synchronisation Jenkins"
          :description="errorMessage"
          close
          @close="liveDataStore.clear(projectIntegration?.id)"
        />
      </UCard>

      <!-- État de chargement initial -->
      <div v-if="isLoading && !liveData" class="space-y-4">
        <div class="p-8 text-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-sky-500 animate-spin mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
            Chargement des jobs depuis Jenkins...
          </p>
          <p class="text-xs text-neutral-500 mt-1">
            Interrogation du dossier Jenkins pour synchroniser les pipelines, builds et statuts d'exécution.
          </p>
        </div>
      </div>

      <div v-else class="space-y-6">
        <!-- 4 Cartes KPIs des Jobs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                  Total des jobs
                </p>
                <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 mt-1">
                  {{ stats.total }}
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                <UIcon name="i-heroicons-queue-list" class="w-5 h-5" />
              </div>
            </div>
            <p class="text-xs text-neutral-500 mt-2 truncate">
              Référencés dans le dossier
            </p>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                  Succès (Vert)
                </p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                  {{ stats.success }}
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <UIcon name="i-heroicons-check-circle" class="w-5 h-5" />
              </div>
            </div>
            <p class="text-xs text-neutral-500 mt-2 truncate">
              Dernier build réussi
            </p>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                  Échecs (Rouge)
                </p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                  {{ stats.failure }}
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center">
                <UIcon name="i-heroicons-x-circle" class="w-5 h-5" />
              </div>
            </div>
            <p class="text-xs text-neutral-500 mt-2 truncate">
              Nécessite une attention
            </p>
          </UCard>

          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                  En cours / Autres
                </p>
                <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 mt-1">
                  {{ stats.building + stats.unstable + stats.disabled }}
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <UIcon name="i-heroicons-clock" class="w-5 h-5" />
              </div>
            </div>
            <p class="text-xs text-neutral-500 mt-2 truncate">
              {{ stats.building }} en cours, {{ stats.unstable }} instable(s)
            </p>
          </UCard>
        </div>

        <!-- Bannière du dernier build exécuté -->
        <div
          v-if="lastBuild"
          class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
        >
          <div class="flex items-center gap-3">
            <span
              class="w-2.5 h-2.5 rounded-full shrink-0"
              :class="lastBuild.result === 'SUCCESS' ? 'bg-emerald-500' : (lastBuild.result === 'FAILURE' ? 'bg-red-500' : 'bg-amber-500')"
            />
            <div>
              <span class="font-semibold text-neutral-900 dark:text-neutral-100">
                Dernière exécution enregistrée :
              </span>
              <span class="font-medium text-neutral-700 dark:text-neutral-300 ml-1">
                {{ lastBuild.jobName }} #{{ lastBuild.number }}
              </span>
              <span class="text-neutral-400 ml-2">
                ({{ formatBuildTime(lastBuild.timestamp) }} - Durée : {{ lastBuild.durationFormatted || '-' }})
              </span>
            </div>
          </div>
          <UButton
            v-if="lastBuild.url"
            :to="lastBuild.url"
            target="_blank"
            color="neutral"
            variant="ghost"
            size="xs"
            icon="i-heroicons-arrow-top-right-on-square"
            label="Consulter les logs du build"
          />
        </div>

        <!-- Liste des Jobs référencés dans le dossier -->
        <UCard :ui="{ body: 'p-0 sm:p-0' }">
          <div class="p-4 sm:p-5 border-b border-neutral-200 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100">
                Jobs référencés dans le dossier
              </h4>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                Pipelines automatisés détectés et gérés par le connecteur Jenkins
              </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
              <!-- Filtre par statut -->
              <div class="flex items-center gap-1 bg-neutral-100 dark:bg-neutral-800 p-1 rounded-xl">
                <button
                  type="button"
                  class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors"
                  :class="statusFilter === 'all' ? 'bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-xs' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200'"
                  @click="statusFilter = 'all'"
                >
                  Tous ({{ jobsList.length }})
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors"
                  :class="statusFilter === 'success' ? 'bg-white dark:bg-neutral-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200'"
                  @click="statusFilter = 'success'"
                >
                  Succès ({{ stats.success }})
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors"
                  :class="statusFilter === 'failure' ? 'bg-white dark:bg-neutral-700 text-red-600 dark:text-red-400 shadow-xs' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200'"
                  @click="statusFilter = 'failure'"
                >
                  Échecs ({{ stats.failure }})
                </button>
              </div>

              <!-- Champ de recherche textuel -->
              <UInput
                v-model="searchQuery"
                icon="i-heroicons-magnifying-glass"
                placeholder="Rechercher un job..."
                size="xs"
                class="w-48 sm:w-56"
              />
            </div>
          </div>

          <!-- Tableau des jobs -->
          <div v-if="filteredJobs.length > 0" class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <div
              v-for="job in filteredJobs"
              :key="job.name"
              class="p-4 sm:p-5 hover:bg-neutral-50/60 dark:hover:bg-neutral-800/40 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div class="flex items-start gap-3 min-w-0">
                <div
                  class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                  :class="getJobIconBgClass(job)"
                >
                  <UIcon :name="getJobIconName(job)" class="w-5 h-5" :class="getJobIconColorClass(job)" />
                </div>
                <div class="min-w-0 space-y-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-sm text-neutral-900 dark:text-neutral-100 truncate">
                      {{ job.displayName || job.name }}
                    </span>
                    <UBadge
                      :color="job.statusBadgeColor"
                      variant="subtle"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span
                        v-if="job.isBuilding"
                        class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-ping"
                      />
                      {{ job.statusLabel || job.status }}
                    </UBadge>
                    <UBadge
                      v-if="job.lastBuild"
                      color="neutral"
                      variant="outline"
                      size="xs"
                      class="font-mono text-[10px]"
                    >
                      #{{ job.lastBuild.number }}
                    </UBadge>
                  </div>

                  <p v-if="cleanDescription(job.description)" class="text-xs text-neutral-500 dark:text-neutral-400 line-clamp-1">
                    {{ cleanDescription(job.description) }}
                  </p>

                  <div class="flex items-center gap-4 text-xs text-neutral-400 flex-wrap">
                    <span v-if="job.lastBuild">
                      Dernier build : {{ formatBuildTime(job.lastBuild.timestamp) }} ({{ job.lastBuild.durationFormatted }})
                    </span>
                    <span v-if="job.lastSuccessfulBuild">
                      Dernier succès : #{{ job.lastSuccessfulBuild.number }}
                    </span>
                    <span v-if="job.lastFailedBuild" class="text-red-500/80">
                      Dernier échec : #{{ job.lastFailedBuild.number }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                <UButton
                  v-if="job.lastBuild?.url"
                  :to="job.lastBuild.url"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  icon="i-heroicons-document-text"
                  label="Logs"
                />
                <UButton
                  v-if="job.url"
                  :to="job.url"
                  target="_blank"
                  color="primary"
                  variant="soft"
                  size="xs"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Voir le job"
                />
              </div>
            </div>
          </div>

          <!-- Aucun job trouvé -->
          <div v-else class="p-8 text-center">
            <UIcon name="i-heroicons-queue-list" class="w-8 h-8 text-neutral-300 dark:text-neutral-600 mx-auto mb-2" />
            <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
              Aucun job Jenkins correspondant
            </p>
            <p class="text-xs text-neutral-500 mt-1">
              {{ searchQuery ? 'Aucun résultat ne correspond à votre filtre.' : 'Aucun job n\'a été détecté dans le dossier configuré.' }}
            </p>
          </div>
        </UCard>
      </div>
    </div>

    <!-- Cas 2 : Jenkins n'est pas encore associé à ce projet -->
    <UCard v-else :ui="{ body: 'p-8 text-center space-y-4' }">
      <div class="w-16 h-16 rounded-3xl bg-sky-500/10 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center mx-auto border border-sky-500/20">
        <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-9 h-9" />
      </div>
      <div class="max-w-md mx-auto space-y-1">
        <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
          Jenkins CI non configuré
        </h3>
        <p class="text-xs text-neutral-500 dark:text-neutral-400">
          Associez un dossier Jenkins à ce projet pour référencer automatiquement tous ses jobs, surveiller les pipelines en temps réel et afficher l'historique des builds.
        </p>
      </div>
      <div>
        <UButton
          color="primary"
          variant="solid"
          size="sm"
          icon="i-heroicons-plus-circle"
          label="Associer Jenkins au projet"
          @click="emit('configure')"
        />
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectintegration";
import type { JenkinsLiveData, JenkinsJob, JenkinsBuild } from "~/types/liveData";
import { useProjectIntegrationLiveDataStore } from "~/stores/projectIntegration/liveData";
import { getExternalUrl } from "~/utils/integration";

const props = defineProps<{
  project?: Project;
  projectIntegration?: ProjectIntegration | null;
}>();

const emit = defineEmits<{
  (e: "configure"): void;
}>();

const liveDataStore = useProjectIntegrationLiveDataStore();

const searchQuery = ref("");
const statusFilter = ref<"all" | "success" | "failure">("all");

const piId = computed(() => props.projectIntegration?.id);

const liveData = computed<JenkinsLiveData | undefined>(() => {
  if (!piId.value) return undefined;
  return liveDataStore.getData<JenkinsLiveData>(piId.value);
});

const isLoading = computed(() => {
  if (!piId.value) return false;
  return liveDataStore.isLoading(piId.value);
});

const errorMessage = computed(() => {
  if (!piId.value) return undefined;
  return liveDataStore.getError(piId.value);
});

// Chemin du dossier configuré
const folderPath = computed(() => {
  return (
    liveData.value?.folder ||
    props.projectIntegration?.parameters?.folder ||
    props.projectIntegration?.parameters?.job_name ||
    ""
  );
});

const displayFolderName = computed(() => {
  if (liveData.value?.folderName) {
    return liveData.value.folderName;
  }
  const f = folderPath.value;
  if (!f) return "Jenkins CI";
  const clean = f.replace(/\/$/, "");
  const parts = clean.split("/");
  return parts[parts.length - 1] || f;
});

const folderExternalUrl = computed(() => {
  if (liveData.value?.url) {
    return liveData.value.url;
  }
  return getExternalUrl(props.projectIntegration);
});

// Liste de tous les jobs référencés
const jobsList = computed<JenkinsJob[]>(() => {
  if (liveData.value?.jobs && liveData.value.jobs.length > 0) {
    return liveData.value.jobs;
  }
  // Repli sur les jobs stockés dans les paramètres de la liaison
  const storedJobs = props.projectIntegration?.parameters?.jobs;
  if (Array.isArray(storedJobs)) {
    return storedJobs.map((j: any) => ({
      name: j.name || "Job",
      displayName: j.displayName || j.name || "Job",
      url: j.url || "",
      color: j.color || "blue",
      class: j.class || "",
      description: j.description || "",
      status: j.status || "SUCCESS",
      statusLabel: j.statusLabel || "Succès",
      statusBadgeColor: j.statusBadgeColor || "success",
      isBuilding: !!j.isBuilding,
      lastBuild: j.lastBuild || null,
      lastSuccessfulBuild: j.lastSuccessfulBuild || null,
      lastFailedBuild: j.lastFailedBuild || null,
    }));
  }
  return [];
});

const filteredJobs = computed(() => {
  let list = jobsList.value;

  if (statusFilter.value === "success") {
    list = list.filter((j) => j.status === "SUCCESS");
  } else if (statusFilter.value === "failure") {
    list = list.filter((j) => j.status === "FAILURE");
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase();
    list = list.filter(
      (j) =>
        j.name.toLowerCase().includes(q) ||
        j.displayName?.toLowerCase().includes(q) ||
        j.description?.toLowerCase().includes(q)
    );
  }

  return list;
});

const stats = computed(() => {
  if (liveData.value?.stats) {
    return liveData.value.stats;
  }
  let success = 0;
  let failure = 0;
  let building = 0;
  let unstable = 0;
  let disabled = 0;

  for (const j of jobsList.value) {
    if (j.isBuilding) building++;
    if (j.status === "SUCCESS") success++;
    else if (j.status === "FAILURE") failure++;
    else if (j.status === "UNSTABLE") unstable++;
    else if (j.status === "DISABLED") disabled++;
  }

  return {
    total: jobsList.value.length,
    success,
    failure,
    building,
    unstable,
    disabled,
  };
});

const lastBuild = computed<JenkinsBuild | null>(() => {
  if (liveData.value?.lastBuild) {
    return liveData.value.lastBuild;
  }
  // Trouver le build le plus récent dans la liste des jobs
  let latest: JenkinsBuild | null = null;
  let maxTs = 0;
  for (const j of jobsList.value) {
    if (j.lastBuild?.timestamp && j.lastBuild.timestamp > maxTs) {
      maxTs = j.lastBuild.timestamp;
      latest = {
        ...j.lastBuild,
        jobName: j.displayName || j.name,
      };
    }
  }
  return latest;
});

function getJobIconBgClass(job: JenkinsJob): string {
  if (job.isBuilding) return "bg-primary-500/10 text-primary-500";
  switch (job.status) {
    case "SUCCESS":
      return "bg-emerald-500/10";
    case "FAILURE":
      return "bg-red-500/10";
    case "UNSTABLE":
      return "bg-amber-500/10";
    default:
      return "bg-neutral-500/10";
  }
}

function getJobIconName(job: JenkinsJob): string {
  if (job.isBuilding) return "i-heroicons-arrow-path";
  switch (job.status) {
    case "SUCCESS":
      return "i-heroicons-check-circle";
    case "FAILURE":
      return "i-heroicons-x-circle";
    case "UNSTABLE":
      return "i-heroicons-exclamation-triangle";
    default:
      return "i-heroicons-minus-circle";
  }
}

function getJobIconColorClass(job: JenkinsJob): string {
  if (job.isBuilding) return "text-primary-500 animate-spin";
  switch (job.status) {
    case "SUCCESS":
      return "text-emerald-500";
    case "FAILURE":
      return "text-red-500";
    case "UNSTABLE":
      return "text-amber-500";
    default:
      return "text-neutral-400";
  }
}

function cleanDescription(desc?: string): string {
  if (!desc) return "";
  return desc.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
}

function formatBuildTime(timestamp?: number): string {
  if (!timestamp) return "-";
  const ts = timestamp < 1e11 ? timestamp * 1000 : timestamp;
  const d = new Date(ts);
  return d.toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function refreshData() {
  if (!piId.value) return;
  await liveDataStore.fetchLiveData(piId.value, true);
}

onMounted(() => {
  if (piId.value) {
    liveDataStore.fetchLiveData(piId.value);
  }
});

watch(
  () => piId.value,
  (newId) => {
    if (newId) {
      liveDataStore.fetchLiveData(newId);
    }
  }
);
</script>
