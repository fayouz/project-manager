<template>
  <div class="space-y-6">
    <!-- Cas 1 : Mantis est configuré pour ce projet -->
    <div v-if="projectIntegration" class="space-y-6">
      <!-- En-tête Suivi des Bogues Mantis BT (Contenu) -->
      <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
              <UIcon name="i-heroicons-bug-ant" class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                  {{ mantisProjectLabel || 'Suivi Mantis BT' }}
                </h3>
                <UBadge color="success" variant="subtle" size="xs">
                  Mantis BT
                </UBadge>
                <UBadge
                  v-if="projectId"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-[11px]"
                >
                  Projet #{{ projectId }}
                </UBadge>
                <UBadge color="neutral" variant="subtle" size="xs">
                  Anomalies actives : {{ stats?.open ?? 0 }}
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-3 flex-wrap">
                <span class="flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                  Taux de résolution : {{ stats?.resolutionRate ?? 0 }}%
                </span>
                <span class="flex items-center gap-1">
                  <UIcon name="i-heroicons-clipboard-document-list" class="w-3.5 h-3.5 text-neutral-400" />
                  {{ stats?.total ?? 0 }} ticket(s) au total
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
              v-if="reportUrl"
              :to="reportUrl"
              target="_blank"
              color="primary"
              variant="solid"
              size="sm"
              icon="i-heroicons-plus-circle"
              label="Signaler un bogue"
            />
            <UButton
              v-if="externalUrl"
              :to="externalUrl"
              target="_blank"
              color="success"
              variant="soft"
              size="sm"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir Mantis"
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

        <!-- Alerte d'erreur de chargement -->
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-heroicons-exclamation-triangle"
          title="Erreur de synchronisation Mantis"
          :description="errorMessage"
          close
          @close="liveDataStore.clear(projectIntegration?.id)"
        />
      </UCard>

      <!-- État de chargement initial -->
      <div v-if="isLoading && !liveData" class="space-y-4">
        <div class="p-8 text-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-emerald-500 animate-spin mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
            Chargement des tickets depuis Mantis BT...
          </p>
          <p class="text-xs text-neutral-500 mt-1">
            Interrogation de l'instance Mantis pour extraire les anomalies, priorités et assignations.
          </p>
        </div>
      </div>

      <div v-else class="space-y-6">
        <!-- Synthèse des indicateurs d'anomalies (KPIs) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- 1. Total anomalies -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Enregistrées</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ stats?.total ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">tickets</span>
                </div>
                <p class="text-xs text-neutral-500 mt-1">Sur le projet Mantis</p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 flex items-center justify-center font-bold border border-neutral-200 dark:border-neutral-700">
                <UIcon name="i-heroicons-clipboard-document-list" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <!-- 2. Anomalies ouvertes -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Anomalies Actives</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ stats?.open ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">à traiter</span>
                </div>
                <p class="text-xs text-neutral-500 mt-1 flex items-center gap-1 font-medium">
                  <UIcon name="i-heroicons-arrow-path" class="w-3.5 h-3.5 text-primary-500" />
                  {{ stats?.inProgress ?? 0 }} en cours / recette
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold border border-rose-200/60 dark:border-rose-800/60">
                <UIcon name="i-heroicons-bug-ant" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <!-- 3. Anomalies en cours -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Nouvelles / À qualifier</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats?.new ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">nouveau(x)</span>
                </div>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1 font-medium">
                  <UIcon name="i-heroicons-sparkles" class="w-3.5 h-3.5" />
                  Nouvelle demande
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold border border-amber-200/60 dark:border-amber-800/60">
                <UIcon name="i-heroicons-clock" class="w-5 h-5" />
              </div>
            </div>
          </UCard>

          <!-- 4. Taux de résolution -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Résolues & Clôturées</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats?.resolved ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">fermées</span>
                </div>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1 font-medium">
                  <UIcon name="i-heroicons-check-circle" class="w-3.5 h-3.5" />
                  {{ stats?.resolutionRate ?? 0 }}% résolues
                </p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold border border-emerald-200/60 dark:border-emerald-800/60">
                <UIcon name="i-heroicons-check-badge" class="w-5 h-5" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Navigation sous-onglets Mantis -->
        <div class="border-b border-neutral-200 dark:border-neutral-800 pb-2">
          <div class="flex items-center gap-2 overflow-x-auto scrollbar-none">
            <UButton
              v-for="sub in contentTabs"
              :key="sub.id"
              :variant="currentContentTab === sub.id ? 'solid' : 'ghost'"
              :color="currentContentTab === sub.id ? 'primary' : 'neutral'"
              size="sm"
              :icon="sub.icon"
              class="text-xs font-medium"
              @click="currentContentTab = sub.id"
            >
              <span>{{ sub.label }}</span>
              <UBadge
                v-if="sub.count !== undefined"
                color="neutral"
                variant="subtle"
                size="xs"
                class="ml-1 text-[10px]"
              >
                {{ sub.count }}
              </UBadge>
            </UButton>
          </div>
        </div>

        <!-- Sous-vue 1 : Liste des anomalies -->
        <div v-show="currentContentTab === 'issues'" class="space-y-4">
          <!-- Filtres de recherche et de statut -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none">
              <UButton
                v-for="f in statusFilters"
                :key="f.id"
                :variant="activeStatusFilter === f.id ? 'solid' : 'outline'"
                :color="activeStatusFilter === f.id ? 'primary' : 'neutral'"
                size="xs"
                @click="activeStatusFilter = f.id"
              >
                {{ f.label }}
              </UButton>
            </div>

            <div class="w-full sm:w-64">
              <UInput
                v-model="searchQuery"
                icon="i-heroicons-magnifying-glass"
                size="xs"
                placeholder="Filtrer par titre, #ID, auteur..."
                class="w-full"
              />
            </div>
          </div>

          <!-- Liste des tickets -->
          <div v-if="filteredIssues.length > 0" class="space-y-2.5">
            <UCard
              v-for="issue in filteredIssues"
              :key="issue.id"
              :ui="{ body: 'p-3.5 sm:p-4' }"
              class="transition hover:border-emerald-500/40"
            >
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                  <div
                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold"
                    :class="getSeverityBgClass(issue.severity)"
                  >
                    <UIcon :name="getSeverityIcon(issue.severity)" class="w-4 h-4" />
                  </div>
                  <div class="min-w-0 space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-mono text-xs font-bold text-neutral-500 dark:text-neutral-400">
                        #{{ issue.id }}
                      </span>
                      <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ issue.summary }}
                      </span>
                      <UBadge
                        :color="getStatusColor(issue.statusCode, issue.status)"
                        variant="subtle"
                        size="xs"
                        class="text-[10px]"
                      >
                        {{ issue.status }}
                      </UBadge>
                      <UBadge
                        v-if="issue.category"
                        color="neutral"
                        variant="subtle"
                        size="xs"
                        class="text-[10px]"
                      >
                        {{ issue.category }}
                      </UBadge>
                    </div>
                    <p v-if="issue.description" class="text-xs text-neutral-600 dark:text-neutral-400 line-clamp-2">
                      {{ issue.description }}
                    </p>
                    <div class="flex items-center gap-3 text-[11px] text-neutral-400 flex-wrap">
                      <span v-if="issue.reporter">Signalé par : <strong class="text-neutral-700 dark:text-neutral-300">{{ issue.reporter }}</strong></span>
                      <span v-if="issue.handler">• Assigné à : <strong class="text-neutral-700 dark:text-neutral-300">{{ issue.handler }}</strong></span>
                      <span v-if="issue.priority">• Priorité : {{ issue.priority }}</span>
                      <span v-if="issue.lastUpdated">• Mis à jour : {{ formatDate(issue.lastUpdated) }}</span>
                    </div>
                  </div>
                </div>

                <UButton
                  v-if="externalUrlBase"
                  :to="`${externalUrlBase}/view.php?id=${issue.id}`"
                  target="_blank"
                  variant="outline"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Voir le ticket"
                  class="shrink-0"
                />
              </div>
            </UCard>
          </div>
          <div v-else class="p-8 text-center rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500">
            Aucun ticket ne correspond aux critères sélectionnés.
          </div>
        </div>

        <!-- Sous-vue 2 : Feuille de route & Jalons -->
        <div v-show="currentContentTab === 'roadmap'" class="space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
            <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <UIcon name="i-heroicons-map" class="w-4 h-4 text-emerald-500" />
              Feuille de route et jalons de version
            </h4>
            <UButton
              v-if="roadmapUrl"
              :to="roadmapUrl"
              target="_blank"
              variant="outline"
              color="neutral"
              size="xs"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Consulter sur Mantis"
            />
          </div>

          <div v-if="roadmap.length > 0" class="space-y-4">
            <UCard
              v-for="milestone in roadmap"
              :key="milestone.name"
              :ui="{ body: 'p-4 sm:p-5 space-y-3' }"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h5 class="text-sm font-bold text-neutral-900 dark:text-neutral-100">
                    {{ milestone.name }}
                  </h5>
                  <p class="text-xs text-neutral-500 mt-0.5">
                    {{ milestone.resolvedIssues }} / {{ milestone.totalIssues }} anomalies résolues
                  </p>
                </div>
                <UBadge
                  :color="milestone.progress >= 100 ? 'success' : 'primary'"
                  variant="subtle"
                  size="xs"
                >
                  {{ milestone.progress }}% complété
                </UBadge>
              </div>

              <div class="w-full bg-neutral-200 dark:bg-neutral-700 h-2 rounded-full overflow-hidden">
                <div
                  class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${milestone.progress}%` }"
                ></div>
              </div>
            </UCard>
          </div>
          <div v-else class="p-8 text-center rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500 space-y-2">
            <p>Aucun jalon ou version cible configuré dans Mantis pour ce projet.</p>
            <UButton
              v-if="roadmapUrl"
              :to="roadmapUrl"
              target="_blank"
              color="success"
              variant="soft"
              size="xs"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Créer un jalon dans Mantis"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Cas 2 : Mantis non configuré sur ce projet -->
    <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
      <div class="max-w-md mx-auto space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/20">
          <UIcon name="i-heroicons-bug-ant" class="w-8 h-8" />
        </div>
        <div>
          <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
            Aucun projet Mantis connecté
          </h3>
          <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1.5 leading-relaxed">
            Ce projet n'est pas encore relié à un espace Mantis BT. Configurez l'intégration dans les paramètres pour suivre les tickets et déclarer des bogues.
          </p>
        </div>

        <div class="pt-2">
          <UButton
            color="success"
            icon="i-heroicons-cog-6-tooth"
            label="Configurer l'intégration Mantis BT"
            size="md"
            @click="emit('configure')"
          />
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import { useProjectIntegrationLiveDataStore } from "~/stores/projectIntegration/liveData";
import type { MantisLiveData } from "~/types/liveData";
import {
  getServer,
  getServerBaseUrl,
  getExternalUrl,
} from "~/utils/integration";

const props = defineProps<{
  project?: Project;
  projectIntegration?: ProjectIntegration | null;
  allIntegrations?: Integration[];
}>();

const emit = defineEmits<{
  (e: "configure"): void;
}>();

const liveDataStore = useProjectIntegrationLiveDataStore();

// Données en direct
const liveData = computed<MantisLiveData | undefined>(() =>
  props.projectIntegration?.id ? liveDataStore.getData<MantisLiveData>(props.projectIntegration.id) : undefined
);

const isLoading = computed(() =>
  props.projectIntegration?.id ? liveDataStore.isLoading(props.projectIntegration.id) : false
);

const errorMessage = computed(() =>
  props.projectIntegration?.id ? liveDataStore.getError(props.projectIntegration.id) : undefined
);

onMounted(() => {
  if (props.projectIntegration?.id) {
    liveDataStore.fetchLiveData<MantisLiveData>(props.projectIntegration.id);
  }
});

watch(
  () => props.projectIntegration?.id,
  (newId) => {
    if (newId) {
      liveDataStore.fetchLiveData<MantisLiveData>(newId);
    }
  }
);

async function refreshData() {
  if (props.projectIntegration?.id) {
    await liveDataStore.fetchLiveData<MantisLiveData>(props.projectIntegration.id, true);
  }
}

// Navigation sous-onglets
const currentContentTab = ref<"issues" | "roadmap">("issues");

// Filtres de statut
const activeStatusFilter = ref<string>("all");
const searchQuery = ref<string>("");

// Paramètres de liaison
const projectId = computed(() => {
  return liveData.value?.projectId || props.projectIntegration?.parameters?.project_id || "";
});

const mantisProjectLabel = computed(() => {
  if (liveData.value?.projectName) {
    return `${liveData.value.projectName} (#${projectId.value})`;
  }
  const p = props.projectIntegration?.parameters || {};
  if (p.project_name && p.project_id) {
    return `${p.project_name} (#${p.project_id})`;
  }
  return p.project_name || (p.project_id ? `Projet #${p.project_id}` : "");
});

const stats = computed(() => liveData.value?.stats);
const issues = computed(() => liveData.value?.issues || []);
const roadmap = computed(() => liveData.value?.roadmap || []);

const contentTabs = computed(() => [
  { id: "issues" as const, label: "Tickets & Bogues", icon: "i-heroicons-bug-ant", count: issues.value.length },
  { id: "roadmap" as const, label: "Feuille de route", icon: "i-heroicons-map", count: roadmap.value.length },
]);

const statusFilters = computed(() => [
  { id: "all", label: `Toutes (${stats.value?.total ?? issues.value.length})` },
  { id: "open", label: `Actives (${stats.value?.open ?? 0})` },
  { id: "in_progress", label: `En cours (${stats.value?.inProgress ?? 0})` },
  { id: "resolved", label: `Résolues (${stats.value?.resolved ?? 0})` },
]);

const filteredIssues = computed(() => {
  let list = issues.value;

  if (activeStatusFilter.value !== "all") {
    list = list.filter((item) => {
      const code = item.statusCode;
      const statusLower = item.status.toLowerCase();
      if (activeStatusFilter.value === "open") {
        return code < 80 && !statusLower.includes("résolu") && !statusLower.includes("fermé");
      }
      if (activeStatusFilter.value === "in_progress") {
        return (code >= 50 && code < 80) || statusLower.includes("recette") || statusLower.includes("cours") || statusLower.includes("affecté");
      }
      if (activeStatusFilter.value === "resolved") {
        return code >= 80 || statusLower.includes("résolu") || statusLower.includes("fermé");
      }
      return true;
    });
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(
      (item) =>
        item.summary.toLowerCase().includes(q) ||
        String(item.id).includes(q) ||
        (item.reporter && item.reporter.toLowerCase().includes(q)) ||
        (item.handler && item.handler.toLowerCase().includes(q))
    );
  }

  return list;
});

const externalUrl = computed(() => {
  if (liveData.value?.url) return liveData.value.url;
  if (!props.projectIntegration) return null;
  return getExternalUrl(props.projectIntegration);
});

const externalUrlBase = computed(() => {
  if (!props.projectIntegration) return "";
  const server = getServer(props.projectIntegration);
  return getServerBaseUrl(server);
});

const reportUrl = computed(() => {
  if (!externalUrlBase.value) return null;
  const pId = projectId.value;
  return pId
    ? `${externalUrlBase.value}/bug_report_page.php?project_id=${encodeURIComponent(pId)}`
    : `${externalUrlBase.value}/bug_report_page.php`;
});

const roadmapUrl = computed(() => {
  if (!externalUrlBase.value) return null;
  const pId = projectId.value;
  return pId
    ? `${externalUrlBase.value}/roadmap_page.php?project_id=${encodeURIComponent(pId)}`
    : `${externalUrlBase.value}/roadmap_page.php`;
});

function getSeverityColor(sev: string): "error" | "warning" | "info" | "neutral" {
  const s = sev?.toLowerCase() || "";
  if (s.includes("bloquant") || s.includes("crash") || s.includes("critique")) return "error";
  if (s.includes("majeur")) return "warning";
  if (s.includes("mineur") || s.includes("texte")) return "info";
  return "neutral";
}

function getSeverityBgClass(sev: string): string {
  const s = sev?.toLowerCase() || "";
  if (s.includes("bloquant") || s.includes("crash") || s.includes("critique")) {
    return "bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20";
  }
  if (s.includes("majeur")) {
    return "bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20";
  }
  if (s.includes("mineur")) {
    return "bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20";
  }
  return "bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20";
}

function getSeverityIcon(sev: string): string {
  const s = sev?.toLowerCase() || "";
  if (s.includes("bloquant") || s.includes("crash") || s.includes("critique")) {
    return "i-heroicons-shield-exclamation";
  }
  if (s.includes("majeur")) {
    return "i-heroicons-exclamation-triangle";
  }
  return "i-heroicons-bug-ant";
}

function getStatusColor(code: number, status: string): "warning" | "primary" | "success" | "neutral" {
  const s = status?.toLowerCase() || "";
  if (code >= 80 || s.includes("résolu") || s.includes("fermé")) return "success";
  if (code >= 50 || s.includes("recette") || s.includes("cours") || s.includes("affecté")) return "primary";
  if (code <= 20 || s.includes("nouveau")) return "warning";
  return "neutral";
}

function formatDate(dateStr?: string): string {
  if (!dateStr) return "Récemment";
  try {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat("fr-FR", {
      dateStyle: "short",
      timeStyle: "short",
    }).format(d);
  } catch {
    return dateStr;
  }
}
</script>
