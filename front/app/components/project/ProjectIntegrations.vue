<template>
  <div class="space-y-6">
    <!-- Barre d'outils des intégrations -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
          Services & Connecteurs associés
        </h3>
        <UBadge color="primary" variant="subtle" size="xs">
          {{ projectIntegrations.length }}
        </UBadge>
      </div>

      <div class="flex items-center gap-2">
        <UButton
          color="primary"
          icon="i-heroicons-plus"
          size="sm"
          label="Associer une intégration"
          @click="emit('create')"
        />
      </div>
    </div>

    <!-- Filtre par type si plusieurs intégrations -->
    <div v-if="projectIntegrations.length > 1" class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
      <UButton
        size="xs"
        :variant="selectedFilter === 'all' ? 'solid' : 'ghost'"
        :color="selectedFilter === 'all' ? 'primary' : 'neutral'"
        label="Toutes"
        @click="selectedFilter = 'all'"
      />
      <UButton
        v-for="filter in availableFilters"
        :key="filter.type"
        size="xs"
        :variant="selectedFilter === filter.type ? 'solid' : 'ghost'"
        :color="selectedFilter === filter.type ? 'primary' : 'neutral'"
        :icon="filter.icon"
        :label="filter.label"
        @click="selectedFilter = filter.type"
      />
    </div>

    <!-- Chargement -->
    <div v-if="isLoading" class="p-8 text-center text-sm text-neutral-500">
      <UIcon name="i-heroicons-arrow-path" class="w-6 h-6 animate-spin mx-auto mb-2 text-primary-500" />
      Chargement des intégrations...
    </div>

    <!-- Liste des intégrations -->
    <div v-else-if="filteredIntegrations.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <UCard
        v-for="pi in filteredIntegrations"
        :key="pi['@id'] || pi.id"
        class="border border-neutral-200/80 dark:border-neutral-800 transition hover:shadow-md"
        :ui="{ body: 'p-5 space-y-4' }"
      >
        <!-- En-tête de la carte -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <div
              class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
              :class="getTypeBgClass(getIntegrationType(pi))"
            >
              <UIcon :name="getTypeIcon(getIntegrationType(pi))" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-sm text-neutral-900 dark:text-neutral-100 truncate">
                  {{ getIntegrationName(pi) }}
                </span>
                <UBadge
                  :color="getTypeBadgeColor(getIntegrationType(pi))"
                  variant="subtle"
                  size="xs"
                  class="uppercase text-[10px]"
                >
                  {{ getIntegrationType(pi) }}
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate mt-0.5 flex items-center gap-1.5">
                <UIcon name="i-heroicons-server" class="w-3.5 h-3.5" />
                <span>{{ getServerNameAndHost(pi) }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <UBadge
              :color="getHealthColor(pi)"
              variant="soft"
              size="xs"
              class="flex items-center gap-1"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getIntegration(pi)?.status)"></span>
              {{ getStatusLabel(getIntegration(pi)?.status) }}
            </UBadge>
          </div>
        </div>

        <!-- Paramètres configurés -->
        <div class="bg-neutral-50 dark:bg-neutral-900/60 rounded-xl p-3 border border-neutral-200/60 dark:border-neutral-800/60 space-y-2">
          <p class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">
            Configuration
          </p>

          <div v-if="hasParameters(pi)" class="space-y-1.5">
            <div
              v-for="(val, key) in pi.parameters"
              :key="key"
              class="flex items-center justify-between text-xs gap-2"
            >
              <span class="text-neutral-500 dark:text-neutral-400 truncate font-medium">
                {{ formatParamKey(String(key), getIntegrationType(pi)) }} :
              </span>
              <span class="font-mono font-medium text-neutral-800 dark:text-neutral-200 truncate bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded text-[11px]">
                {{ val }}
              </span>
            </div>
          </div>
          <p v-else class="text-xs text-neutral-400 italic">
            Aucun paramètre spécifique requis.
          </p>
        </div>

        <!-- Pied de carte : Actions -->
        <div class="flex items-center justify-between pt-2 border-t border-neutral-100 dark:border-neutral-800/80 gap-2">
          <div class="flex items-center gap-1">
            <UButton
              v-if="getExternalUrl(pi)"
              :to="getExternalUrl(pi)!"
              target="_blank"
              variant="ghost"
              color="primary"
              size="xs"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Accéder"
            />
            <UButton
              variant="ghost"
              color="neutral"
              size="xs"
              icon="i-heroicons-arrow-path"
              :loading="testingIds.includes(String(pi.id || pi['@id']))"
              title="Tester la connectivité"
              @click="testIntegration(pi)"
            />
          </div>

          <div class="flex items-center gap-1">
            <UButton
              variant="ghost"
              color="neutral"
              size="xs"
              icon="i-heroicons-pencil-square"
              label="Modifier"
              @click="emit('edit', pi)"
            />
            <UButton
              variant="ghost"
              color="error"
              size="xs"
              icon="i-heroicons-link-slash"
              title="Dissocier cette intégration"
              @click="emit('unlink', pi)"
            />
          </div>
        </div>
      </UCard>
    </div>

    <!-- Cas vide : Aucune intégration -->
    <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
      <div class="max-w-md mx-auto space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center mx-auto border border-primary-500/20">
          <UIcon name="i-heroicons-puzzle-piece" class="w-8 h-8" />
        </div>
        <div>
          <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
            Aucune intégration associée
          </h3>
          <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1.5 leading-relaxed">
            Associez des outils DevOps à ce projet (Gitea pour le code, SonarQube pour l'analyse de qualité, Mantis BT pour le suivi des bogues, Jenkins pour l'intégration continue, Nexus pour les dépôts d'artefacts).
          </p>
        </div>

        <div class="pt-2">
          <UButton
            color="primary"
            icon="i-heroicons-plus"
            label="Associer une première intégration"
            size="md"
            @click="emit('create')"
          />
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import {
  getIntegration,
  getIntegrationType,
  getIntegrationName,
  getServer,
  getExternalUrl,
  getTypeIcon,
  getTypeBadgeColor,
  getTypeBgClass,
  getStatusDotClass,
  getStatusLabel,
  formatParamKey,
  getServerNameAndHost,
} from "~/utils/integration";
import { useIntegrationTestStore } from "~/stores/integration/test";
import { getIdFromIri } from "~/utils/resource";

const props = defineProps<{
  project: Project;
  projectIntegrations: ProjectIntegration[];
  allIntegrations: Integration[];
  isLoading?: boolean;
}>();

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", pi: ProjectIntegration): void;
  (e: "unlink", pi: ProjectIntegration): void;
  (e: "tested"): void;
}>();

const selectedFilter = ref<string>("all");
const testingIds = ref<string[]>([]);
const testStore = useIntegrationTestStore();

const availableFilters = computed(() => {
  const types = new Set<string>();
  props.projectIntegrations.forEach((pi) => {
    const t = getIntegrationType(pi);
    if (t) types.add(t);
  });

  return Array.from(types).map((t) => ({
    type: t,
    label: t.charAt(0).toUpperCase() + t.slice(1),
    icon: getTypeIcon(t),
  }));
});

const filteredIntegrations = computed(() => {
  if (selectedFilter.value === "all") {
    return props.projectIntegrations;
  }
  return props.projectIntegrations.filter(
    (pi) => getIntegrationType(pi) === selectedFilter.value
  );
});

function hasParameters(pi: ProjectIntegration): boolean {
  return !!pi.parameters && Object.keys(pi.parameters).length > 0;
}

function getHealthColor(pi: ProjectIntegration): "success" | "warning" | "error" | "neutral" {
  const status = getIntegration(pi)?.status?.toLowerCase();
  switch (status) {
    case "healthy":
    case "ok":
      return "success";
    case "warning":
      return "warning";
    case "error":
      return "error";
    default:
      return "neutral";
  }
}

async function testIntegration(pi: ProjectIntegration) {
  const integ = getIntegration(pi);
  if (!integ) return;
  const id = getIdFromIri(integ["@id"]) || integ.id;
  if (!id) return;

  const key = String(pi.id || pi["@id"]);
  testingIds.value.push(key);

  try {
    await testStore.testExisting(id);
    emit("tested");
  } catch {
    //
  } finally {
    testingIds.value = testingIds.value.filter((k) => k !== key);
  }
}
</script>
