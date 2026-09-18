<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Organisations">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Organisations</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredOrganisations.length }} organisation{{ filteredOrganisations.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouvelle organisation"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Liste des organisations</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">Gérez les entités et structures clientes du workspace.</p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher une organisation..."
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
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des organisations'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des organisations...</span>
      </div>

      <!-- Aucune organisation trouvée -->
      <div v-else-if="filteredOrganisations.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-building-office-2" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucune organisation pour le moment' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Créez votre première organisation pour regrouper vos projets.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouvelle organisation"
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

      <!-- Vue Grille (Cartes personnalisées) -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <UCard
          v-for="org in filteredOrganisations"
          :key="org['@id'] || org.id"
          class="flex flex-col justify-between hover:shadow-md transition-shadow"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-building-office-2" class="w-5 h-5" />
              </div>
              <UBadge
                color="primary"
                variant="subtle"
                size="xs"
              >
                ID: {{ getIdFromIri(org['@id']) || org.id }}
              </UBadge>
            </div>

            <div>
              <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer" @click="goToShow(org)">
                {{ org.name || 'Organisation sans nom' }}
              </h3>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                {{ org.projects?.length || 0 }} projet{{ (org.projects?.length || 0) > 1 ? 's' : '' }} associé{{ (org.projects?.length || 0) > 1 ? 's' : '' }}
              </p>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="flex items-center gap-1 font-mono">
                {{ org['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/organisations/${getIdFromIri(org['@id']) || org.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir l'organisation"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier l'organisation"
                  @click="openEditModal(org)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer l'organisation"
                  @click="handleDelete(org)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau (Composant OrganisationList généré) -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <OrganisationList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouvelle organisation">
      <template #body>
        <OrganisationCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'organisation">
      <template #body>
        <OrganisationUpdate
          v-if="selectedOrg"
          :id="selectedOrgId"
          :item="selectedOrg"
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
import type { Organisation } from "~/types/organisation";
import { useOrganisationListStore } from "~/stores/organisation/list";
import { useOrganisationDeleteStore } from "~/stores/organisation/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import OrganisationCreate from "~/components/organisation/OrganisationCreate.vue";
import OrganisationUpdate from "~/components/organisation/OrganisationUpdate.vue";
import OrganisationList from "~/components/organisation/OrganisationList.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Organisations - Project Manager",
});

// Stores
const organisationListStore = useOrganisationListStore();
const organisationDeleteStore = useOrganisationDeleteStore();
const { items, isLoading, error } = storeToRefs(organisationListStore);

// Synchronisation Mercure temps réel
useMercureList({
  store: organisationListStore,
  deleteStore: organisationDeleteStore,
});

// Chargement initial des données depuis l'API
async function loadOrganisations() {
  const data = await useFetchList<Organisation>("organisations");
  organisationListStore.setData(data);
}
await loadOrganisations();

// État local de la vue
const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

// Modales
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedOrg = ref<Organisation | null>(null);

const selectedOrgId = computed(() => {
  if (!selectedOrg.value) return undefined;
  return (
    getIdFromIri(selectedOrg.value["@id"]) ||
    String(selectedOrg.value.id || "")
  );
});

// Navigation vers la vue Show
function goToShow(org: Organisation) {
  const id = getIdFromIri(org["@id"]) || org.id;
  if (id) {
    navigateTo(`/organisations/${id}`);
  }
}

// Filtrage réactif par recherche
const filteredOrganisations = computed(() => {
  const list = items.value || [];
  if (!searchQuery.value.trim()) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter((o: Organisation) => o.name?.toLowerCase().includes(q));
});

// Actions modales
function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(org: Organisation) {
  selectedOrg.value = org;
  isEditModalOpen.value = true;
}

// Événements CRUD
async function onCreated(createdOrg: Organisation) {
  isCreateModalOpen.value = false;
  await loadOrganisations();
}

function onUpdated(updatedOrg: Organisation) {
  isEditModalOpen.value = false;
  organisationListStore.updateItem(updatedOrg);
}

function onDeleted(deletedOrg: Organisation) {
  isEditModalOpen.value = false;
  organisationListStore.deleteItem(deletedOrg);
}

async function handleDelete(org: Organisation) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'organisation "${org.name || org['@id']}" ?`)) {
    const { error: delError } = await useDeleteItem(org);
    if (!delError.value) {
      organisationListStore.deleteItem(org);
      organisationDeleteStore.setDeleted(org);
    }
  }
}
</script>
