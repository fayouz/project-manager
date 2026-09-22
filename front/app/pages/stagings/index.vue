<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Stagings">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Environnements Staging</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredStagings.length }} environnement{{ filteredStagings.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouveau staging"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Tous les environnements Staging</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">Consultez et administrez les déploiements de tous les projets.</p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher un staging..."
            icon="i-heroicons-magnifying-glass"
            size="sm"
            class="w-64"
          />

          <!-- Sélecteur de vue Grille / Tableau -->
          <div class="flex items-center border border-neutral-200 dark:border-neutral-800 rounded-lg p-0.5 bg-neutral-100 dark:bg-neutral-800">
            <UButton
              size="xs"
              :variant="viewMode === 'grid' ? 'solid' : 'ghost'"
              :color="viewMode === 'grid' ? 'primary' : 'neutral'"
              icon="i-heroicons-squares-2x2"
              aria-label="Vue grille"
              @click="viewMode = 'grid'"
            />
            <UButton
              size="xs"
              :variant="viewMode === 'table' ? 'solid' : 'ghost'"
              :color="viewMode === 'table' ? 'primary' : 'neutral'"
              icon="i-heroicons-bars-3"
              aria-label="Vue tableau"
              @click="viewMode = 'table'"
            />
          </div>
        </div>
      </div>

      <!-- Alerte d'erreur -->
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des stagings'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des environnements...</span>
      </div>

      <!-- Aucun staging trouvé -->
      <div v-else-if="filteredStagings.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-rocket-launch" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun environnement Staging pour le moment' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Créez votre premier environnement depuis la fiche projet ou ici.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau staging"
            @click="openCreateModal"
          />
          <UButton
            v-else
            variant="ghost"
            color="neutral"
            label="Effacer la recherche"
            @click="searchQuery = ''"
          />
        </div>
      </div>

      <!-- Vue Grille -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <UCard
          v-for="staging in filteredStagings"
          :key="staging['@id'] || staging.id"
          class="flex flex-col justify-between hover:border-primary/50 transition-colors shadow-sm"
          :ui="{ body: 'p-5 space-y-3 flex-1 flex flex-col justify-between' }"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                  <UIcon name="i-heroicons-rocket-launch" class="size-6" />
                </div>
                <div>
                  <h3 class="font-bold text-base text-neutral-900 dark:text-neutral-100">
                    {{ staging.name || 'Staging' }}
                  </h3>
                  <div class="flex items-center gap-2 mt-0.5">
                    <UBadge color="primary" variant="subtle" size="xs">
                      {{ staging.environment || 'staging' }}
                    </UBadge>
                    <UBadge color="neutral" variant="outline" size="xs">
                      {{ staging.status || 'actif' }}
                    </UBadge>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="staging.branch" class="text-xs text-neutral-500 font-mono">
              Branche : {{ staging.branch }}
            </p>

            <p v-if="staging.description" class="text-xs text-neutral-600 dark:text-neutral-400 line-clamp-2">
              {{ staging.description }}
            </p>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="flex items-center gap-1 font-mono">
                {{ staging['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/stagings/${getIdFromIri(staging['@id']) || staging.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir le staging"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier le staging"
                  @click="openEditModal(staging)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer le staging"
                  @click="handleDelete(staging)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <StagingList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouveau staging">
      <template #body>
        <StagingCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le staging">
      <template #body>
        <StagingUpdate
          v-if="selectedStaging"
          :id="selectedStagingId"
          :item="selectedStaging"
          :show-back="false"
          @updated="onUpdated"
          @deleted="onDeleted"
          @cancel="isEditModalOpen = false"
        />
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { storeToRefs } from "pinia";
import type { Staging } from "~/types/staging";
import { useStagingListStore } from "~/stores/staging/list";
import { useStagingDeleteStore } from "~/stores/staging/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import StagingCreate from "~/components/staging/StagingCreate.vue";
import StagingUpdate from "~/components/staging/StagingUpdate.vue";
import StagingList from "~/components/staging/StagingList.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Stagings - Project Manager",
});

const stagingListStore = useStagingListStore();
const stagingDeleteStore = useStagingDeleteStore();
const { items, isLoading, error } = storeToRefs(stagingListStore);

useMercureList({
  store: stagingListStore,
  deleteStore: stagingDeleteStore,
});

async function loadStagings() {
  const data = await useFetchList<Staging>("stagings");
  stagingListStore.setData(data);
}
await loadStagings();

const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedStaging = ref<Staging | null>(null);

const selectedStagingId = computed(() => {
  if (!selectedStaging.value) return "";
  return String(getIdFromIri(selectedStaging.value["@id"]) || selectedStaging.value.id || "");
});

const filteredStagings = computed(() => {
  if (!items.value) return [];
  if (!searchQuery.value) return items.value;
  const q = searchQuery.value.toLowerCase();
  return items.value.filter((s) => {
    return (
      s.name?.toLowerCase().includes(q) ||
      s.environment?.toLowerCase().includes(q) ||
      s.status?.toLowerCase().includes(q) ||
      s.branch?.toLowerCase().includes(q) ||
      s.description?.toLowerCase().includes(q)
    );
  });
});

function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(staging: Staging) {
  selectedStaging.value = staging;
  isEditModalOpen.value = true;
}

function goToShow(staging: Staging) {
  const id = getIdFromIri(staging["@id"]) || staging.id;
  navigateTo(`/stagings/${id}`);
}

async function onCreated() {
  isCreateModalOpen.value = false;
  await loadStagings();
}

async function onUpdated() {
  isEditModalOpen.value = false;
  selectedStaging.value = null;
  await loadStagings();
}

async function onDeleted() {
  isEditModalOpen.value = false;
  selectedStaging.value = null;
  await loadStagings();
}

async function handleDelete(staging: Staging) {
  const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer le staging "${staging.name || 'Sélectionné'}" ?`);
  if (!confirmed) return;

  try {
    await useDeleteItem(staging);
    await loadStagings();
  } catch (err: any) {
    alert(err?.message || "Erreur lors de la suppression du staging.");
  }
}
</script>
