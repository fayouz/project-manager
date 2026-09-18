<template>
  <div class="space-y-6">
    <!-- Cas 1 : SonarQube est configuré pour ce projet -->
    <div v-if="projectIntegration" class="space-y-6">
      <!-- En-tête Qualité de Code SonarQube (Contenu) -->
      <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20">
              <UIcon name="i-heroicons-shield-check" class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                  {{ projectName || projectKey || 'Analyse SonarQube' }}
                </h3>
                <UBadge color="primary" variant="subtle" size="xs">
                  Clean Code
                </UBadge>
                <UBadge
                  :color="qualityGateColor"
                  variant="subtle"
                  size="xs"
                  class="flex items-center gap-1 font-semibold"
                >
                  <UIcon :name="qualityGateIcon" class="w-3.5 h-3.5" />
                  Quality Gate : {{ qualityGateLabel }}
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-3 flex-wrap">
                <span class="flex items-center gap-1 font-mono text-[11px]">
                  Clé du projet : {{ projectKey }}
                </span>
                <span v-if="metrics" class="flex items-center gap-1">
                  <UIcon name="i-heroicons-bars-3-bottom-left" class="w-3.5 h-3.5 text-neutral-400" />
                  {{ metrics.linesOfCode }} lignes de code (NCLOC)
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
              v-if="externalUrl"
              :to="externalUrl"
              target="_blank"
              color="primary"
              variant="solid"
              size="sm"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir SonarQube"
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
          title="Erreur de synchronisation SonarQube"
          :description="errorMessage"
          close
          @close="liveDataStore.clear(projectIntegration?.id)"
        />

        <!-- Bannière d'état Quality Gate -->
        <div
          class="p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
          :class="qualityGateBannerClass"
        >
          <div class="flex items-center gap-2.5 font-medium">
            <UIcon :name="qualityGateIcon" class="w-5 h-5 shrink-0" />
            <span>{{ qualityGateDescription }}</span>
          </div>
          <span class="font-semibold shrink-0">
            {{ metrics?.codeSmells || 0 }} Dette / Code Smell(s)
          </span>
        </div>
      </UCard>

      <!-- État de chargement initial -->
      <div v-if="isLoading && !liveData" class="space-y-4">
        <div class="p-8 text-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-blue-500 animate-spin mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
            Chargement des métriques depuis SonarQube...
          </p>
          <p class="text-xs text-neutral-500 mt-1">
            Calcul de la dette technique, fiabilité, sécurité et couverture de code.
          </p>
        </div>
      </div>

      <div v-else class="space-y-6">
        <!-- Les 4 Piliers Clean Code de SonarQube -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- 1. Fiabilité (Bugs) -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Fiabilité</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ metrics?.bugs ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">bug(s)</span>
                </div>
                <p class="text-xs mt-1 flex items-center gap-1 font-medium" :class="getRatingColor(metrics?.reliabilityRating)">
                  <UIcon :name="metrics?.bugs === 0 ? 'i-heroicons-check' : 'i-heroicons-exclamation-triangle'" class="w-3.5 h-3.5" />
                  Note {{ metrics?.reliabilityRating || 'A' }}
                </p>
              </div>
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base border"
                :class="getRatingBadgeClass(metrics?.reliabilityRating)"
              >
                {{ metrics?.reliabilityRating || 'A' }}
              </div>
            </div>
          </UCard>

          <!-- 2. Sécurité (Vulnérabilités) -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Sécurité</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ metrics?.vulnerabilities ?? 0 }}</span>
                  <span class="text-xs text-neutral-500">vulnérabilité(s)</span>
                </div>
                <p class="text-xs mt-1 flex items-center gap-1 font-medium" :class="getRatingColor(metrics?.securityRating)">
                  <UIcon name="i-heroicons-shield-check" class="w-3.5 h-3.5" />
                  Note {{ metrics?.securityRating || 'A' }}
                </p>
              </div>
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base border"
                :class="getRatingBadgeClass(metrics?.securityRating)"
              >
                {{ metrics?.securityRating || 'A' }}
              </div>
            </div>
          </UCard>

          <!-- 3. Maintenabilité (Dette technique & Code Smells) -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Maintenabilité</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ metrics?.debtDisplay || '0min' }}</span>
                </div>
                <p class="text-xs mt-1 flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400">
                  <UIcon name="i-heroicons-wrench-screwdriver" class="w-3.5 h-3.5" />
                  {{ metrics?.codeSmells ?? 0 }} code smell(s)
                </p>
              </div>
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base border"
                :class="getRatingBadgeClass(metrics?.maintainabilityRating)"
              >
                {{ metrics?.maintainabilityRating || 'A' }}
              </div>
            </div>
          </UCard>

          <!-- 4. Couverture des tests -->
          <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Couverture & Tests</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ metrics?.coverage ?? 0 }}%</span>
                </div>
                <p class="text-xs text-neutral-500 mt-1 flex items-center gap-1 font-medium">
                  <UIcon name="i-heroicons-document-duplicate" class="w-3.5 h-3.5" />
                  {{ metrics?.duplications ?? 0 }}% duplication
                </p>
              </div>
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base border"
                :class="(metrics?.coverage ?? 0) >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800'"
              >
                <UIcon name="i-heroicons-beaker" class="w-5 h-5" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Navigation sous-onglets SonarQube -->
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

        <!-- Sous-vue 1 : Anomalies & Code Smells -->
        <div v-show="currentContentTab === 'issues'" class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-4 h-4 text-amber-500" />
              Anomalies de code et dette technique
            </h4>
            <span class="text-xs text-neutral-500">
              {{ issues.length }} anomalie(s) répertoriée(s)
            </span>
          </div>

          <div v-if="issues.length > 0" class="space-y-2.5">
            <UCard
              v-for="issue in issues"
              :key="issue.key"
              :ui="{ body: 'p-3.5 sm:p-4' }"
              class="transition hover:border-blue-500/40"
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
                    <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                      {{ issue.message }}
                    </p>
                    <div class="flex items-center gap-2 flex-wrap text-xs text-neutral-500 dark:text-neutral-400">
                      <span class="font-mono text-[11px] text-primary-600 dark:text-primary-400 font-medium">
                        {{ issue.component }}<span v-if="issue.line">:{{ issue.line }}</span>
                      </span>
                      <span>•</span>
                      <UBadge
                        :color="getSeverityColor(issue.severity)"
                        variant="subtle"
                        size="xs"
                        class="text-[10px]"
                      >
                        {{ issue.severity }}
                      </UBadge>
                      <UBadge color="neutral" variant="subtle" size="xs" class="text-[10px]">
                        {{ issue.type }}
                      </UBadge>
                      <span v-if="issue.effort">• Effort : {{ issue.effort }}</span>
                    </div>
                  </div>
                </div>

                <UButton
                  v-if="externalUrl"
                  :to="`${externalUrl}/project/issues?id=${encodeURIComponent(projectKey)}&issues=${encodeURIComponent(issue.key)}&open=${encodeURIComponent(issue.key)}`"
                  target="_blank"
                  variant="outline"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Voir la règle"
                />
              </div>
            </UCard>
          </div>
          <div v-else class="p-8 text-center rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500">
            Aucune anomalie non résolue. Code propre !
          </div>
        </div>

        <!-- Sous-vue 2 : Conditions du Quality Gate -->
        <div v-show="currentContentTab === 'conditions'" class="space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
            <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <UIcon name="i-heroicons-shield-exclamation" class="w-4 h-4 text-blue-500" />
              Conditions d'acceptation du Quality Gate
            </h4>
            <UBadge :color="qualityGateColor" variant="subtle" size="xs">
              {{ qualityGateLabel }}
            </UBadge>
          </div>

          <div v-if="qualityGateConditions.length > 0" class="space-y-2">
            <div
              v-for="cond in qualityGateConditions"
              :key="cond.metric"
              class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center justify-between gap-3 text-xs"
            >
              <div class="space-y-0.5">
                <span class="font-semibold text-neutral-900 dark:text-neutral-100 font-mono">
                  {{ cond.metric }}
                </span>
                <p class="text-neutral-500">
                  Seuil attendu : {{ cond.operator }} {{ cond.errorThreshold }} • Valeur constatée : <strong class="text-neutral-800 dark:text-neutral-200">{{ cond.actualValue }}</strong>
                </p>
              </div>

              <UBadge
                :color="cond.status === 'OK' ? 'success' : 'error'"
                variant="subtle"
                size="xs"
                class="shrink-0"
              >
                {{ cond.status === 'OK' ? 'Validé' : 'Échec' }}
              </UBadge>
            </div>
          </div>
          <div v-else class="p-6 text-center text-xs text-neutral-500 bg-neutral-50 dark:bg-neutral-800/30 rounded-xl border border-neutral-200 dark:border-neutral-700">
            Toutes les conditions par défaut sont respectées.
          </div>
        </div>

        <!-- Sous-vue 3 : Métriques détaillées -->
        <div v-show="currentContentTab === 'measures'" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="m in measureCards"
              :key="m.name"
              class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ m.name }}</span>
                <UIcon :name="m.icon" class="w-4 h-4 text-blue-500" />
              </div>
              <p class="text-xl font-bold font-mono text-neutral-900 dark:text-neutral-100">
                {{ m.value }}
              </p>
              <p class="text-[11px] text-neutral-400">{{ m.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cas 2 : SonarQube non configuré sur ce projet -->
    <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
      <div class="max-w-md mx-auto space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto border border-blue-500/20">
          <UIcon name="i-heroicons-shield-check" class="w-8 h-8" />
        </div>
        <div>
          <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
            Aucun projet SonarQube connecté
          </h3>
          <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1.5 leading-relaxed">
            Ce projet n'est pas encore relié à SonarQube. Associez une clé de projet pour suivre la couverture de tests, les failles de sécurité et la dette technique.
          </p>
        </div>

        <div class="pt-2">
          <UButton
            color="primary"
            icon="i-heroicons-cog-6-tooth"
            label="Configurer l'intégration SonarQube"
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
import type { SonarQubeLiveData } from "~/types/liveData";
import { getExternalUrl } from "~/utils/integration";

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
const liveData = computed<SonarQubeLiveData | undefined>(() =>
  props.projectIntegration?.id ? liveDataStore.getData<SonarQubeLiveData>(props.projectIntegration.id) : undefined
);

const isLoading = computed(() =>
  props.projectIntegration?.id ? liveDataStore.isLoading(props.projectIntegration.id) : false
);

const errorMessage = computed(() =>
  props.projectIntegration?.id ? liveDataStore.getError(props.projectIntegration.id) : undefined
);

onMounted(() => {
  if (props.projectIntegration?.id) {
    liveDataStore.fetchLiveData<SonarQubeLiveData>(props.projectIntegration.id);
  }
});

watch(
  () => props.projectIntegration?.id,
  (newId) => {
    if (newId) {
      liveDataStore.fetchLiveData<SonarQubeLiveData>(newId);
    }
  }
);

async function refreshData() {
  if (props.projectIntegration?.id) {
    await liveDataStore.fetchLiveData<SonarQubeLiveData>(props.projectIntegration.id, true);
  }
}

// Navigation sous-onglets
const currentContentTab = ref<"issues" | "conditions" | "measures">("issues");

// Données calculées
const projectKey = computed(() => {
  return liveData.value?.projectKey || props.projectIntegration?.parameters?.project_key || "";
});

const projectName = computed(() => {
  return liveData.value?.name || projectKey.value;
});

const metrics = computed(() => liveData.value?.metrics);
const issues = computed(() => liveData.value?.issues || []);
const qualityGateConditions = computed(() => liveData.value?.qualityGate?.conditions || []);

const qualityGateStatus = computed(() => {
  return liveData.value?.qualityGate?.status || "UNKNOWN";
});

const qualityGateLabel = computed(() => {
  switch (qualityGateStatus.value) {
    case "OK":
      return "Conforme";
    case "ERROR":
      return "Non conforme";
    case "WARN":
      return "Avertissement";
    default:
      return "Non évalué";
  }
});

const qualityGateColor = computed<"success" | "error" | "warning" | "neutral">(() => {
  switch (qualityGateStatus.value) {
    case "OK":
      return "success";
    case "ERROR":
      return "error";
    case "WARN":
      return "warning";
    default:
      return "neutral";
  }
});

const qualityGateIcon = computed(() => {
  switch (qualityGateStatus.value) {
    case "OK":
      return "i-heroicons-check-circle";
    case "ERROR":
      return "i-heroicons-x-circle";
    case "WARN":
      return "i-heroicons-exclamation-triangle";
    default:
      return "i-heroicons-question-mark-circle";
  }
});

const qualityGateBannerClass = computed(() => {
  switch (qualityGateStatus.value) {
    case "OK":
      return "bg-emerald-500/10 border-emerald-500/20 text-emerald-800 dark:text-emerald-200";
    case "ERROR":
      return "bg-rose-500/10 border-rose-500/20 text-rose-800 dark:text-rose-200";
    case "WARN":
      return "bg-amber-500/10 border-amber-500/20 text-amber-800 dark:text-amber-200";
    default:
      return "bg-neutral-500/10 border-neutral-500/20 text-neutral-800 dark:text-neutral-200";
  }
});

const qualityGateDescription = computed(() => {
  switch (qualityGateStatus.value) {
    case "OK":
      return "Tous les critères d'acceptation du Quality Gate sont validés avec succès.";
    case "ERROR":
      return "Le Quality Gate a échoué. Des seuils de couverture ou de qualité ne sont pas atteints.";
    case "WARN":
      return "Des alertes potentielles ont été détectées sur ce projet.";
    default:
      return "Statut du Quality Gate en attente d'analyse.";
  }
});

const contentTabs = computed(() => [
  { id: "issues" as const, label: "Anomalies & Dette", icon: "i-heroicons-exclamation-triangle", count: issues.value.length },
  { id: "conditions" as const, label: "Quality Gate", icon: "i-heroicons-shield-check", count: qualityGateConditions.value.length },
  { id: "measures" as const, label: "Mesures & Métriques", icon: "i-heroicons-chart-bar" },
]);

const externalUrl = computed(() => {
  if (liveData.value?.url) return liveData.value.url;
  if (!props.projectIntegration) return null;
  return getExternalUrl(props.projectIntegration);
});

const measureCards = computed(() => [
  { name: "Lignes de code (LOC)", value: String(metrics.value?.linesOfCode ?? 0), icon: "i-heroicons-bars-3-bottom-left", description: "Lignes exécutables analysées (NCLOC)" },
  { name: "Taux de couverture", value: `${metrics.value?.coverage ?? 0}%`, icon: "i-heroicons-beaker", description: "Couverture par les tests automatisés" },
  { name: "Duplication du code", value: `${metrics.value?.duplications ?? 0}%`, icon: "i-heroicons-document-duplicate", description: "Densité de lignes dupliquées" },
  { name: "Dette technique totale", value: metrics.value?.debtDisplay || "0min", icon: "i-heroicons-clock", description: "Temps estimé de remédiation" },
  { name: "Code Smells ouverts", value: String(metrics.value?.codeSmells ?? 0), icon: "i-heroicons-wrench-screwdriver", description: "Maintenabilité globale du code" },
  { name: "Points chauds de sécurité", value: String(metrics.value?.securityHotspots ?? 0), icon: "i-heroicons-shield-exclamation", description: "Zones nécessitant une révision manuelle" },
]);

function getRatingColor(rating?: string): string {
  switch (rating) {
    case "A":
      return "text-emerald-600 dark:text-emerald-400";
    case "B":
      return "text-lime-600 dark:text-lime-400";
    case "C":
      return "text-amber-600 dark:text-amber-400";
    case "D":
    case "E":
      return "text-rose-600 dark:text-rose-400";
    default:
      return "text-neutral-500";
  }
}

function getRatingBadgeClass(rating?: string): string {
  switch (rating) {
    case "A":
      return "bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800";
    case "B":
      return "bg-lime-50 dark:bg-lime-950/40 text-lime-600 dark:text-lime-400 border-lime-200 dark:border-lime-800";
    case "C":
      return "bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800";
    case "D":
    case "E":
      return "bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800";
    default:
      return "bg-neutral-100 text-neutral-600 border-neutral-200";
  }
}

function getSeverityColor(severity: string): "error" | "warning" | "info" | "neutral" {
  switch (severity?.toUpperCase()) {
    case "BLOCKER":
    case "CRITICAL":
      return "error";
    case "MAJOR":
      return "warning";
    case "MINOR":
    case "INFO":
      return "info";
    default:
      return "neutral";
  }
}

function getSeverityBgClass(severity: string): string {
  switch (severity?.toUpperCase()) {
    case "BLOCKER":
    case "CRITICAL":
      return "bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20";
    case "MAJOR":
      return "bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20";
    case "MINOR":
    case "INFO":
      return "bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20";
    default:
      return "bg-neutral-500/10 text-neutral-600 dark:text-neutral-400 border border-neutral-500/20";
  }
}

function getSeverityIcon(severity: string): string {
  switch (severity?.toUpperCase()) {
    case "BLOCKER":
    case "CRITICAL":
      return "i-heroicons-shield-exclamation";
    case "MAJOR":
      return "i-heroicons-exclamation-triangle";
    default:
      return "i-heroicons-information-circle";
  }
}
</script>
