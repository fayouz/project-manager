<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Navbar du Dashboard -->
    <UDashboardNavbar title="Intégrations">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Intégrations</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredIntegrations.length }} outil{{ filteredIntegrations.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouvelle intégration"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Connecteurs & Outils externes</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Supervisez l'état et configurez les intégrations tierces (Jenkins, Gitea, SonarQube, Mantis, etc.).
          </p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher une intégration..."
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

      <!-- Notification test rapide -->
      <UAlert
        v-if="quickTestNotification"
        :color="quickTestNotification.success ? 'success' : 'error'"
        :icon="quickTestNotification.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
        variant="subtle"
        :title="quickTestNotification.title"
        class="mb-2"
        :close="{ color: 'neutral', variant: 'ghost', class: '-my-1.5' }"
        @close="quickTestNotification = null"
      />

      <!-- Alerte d'erreur générale -->
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des intégrations'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des intégrations...</span>
      </div>

      <!-- Aucune intégration trouvée -->
      <div v-else-if="filteredIntegrations.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-puzzle-piece" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucune intégration configurée' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500 max-w-sm mx-auto">
          {{ searchQuery ? 'Modifiez votre requête ou réinitialisez la recherche.' : 'Connectez vos premiers outils de CI/CD, forges git ou gestionnaires de tickets.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Ajouter une intégration"
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

      <!-- Vue Grille (Cartes personnalisées Nuxt UI) -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <UCard
          v-for="item in filteredIntegrations"
          :key="item['@id'] || item.id"
          class="flex flex-col justify-between hover:shadow-md transition-shadow group border border-neutral-200 dark:border-neutral-800"
        >
          <div class="space-y-4">
            <!-- Header de carte -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <div
                  class="size-10 rounded-xl flex items-center justify-center shrink-0 font-semibold"
                  :class="getTypeColorClass(item.type)"
                >
                  <UIcon :name="getIntegrationIcon(item.type)" class="size-5" />
                </div>
                <div class="min-w-0">
                  <span class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                    {{ item.type || 'Inconnu' }}
                  </span>
                  <div class="flex items-center gap-1.5">
                    <span
                      class="size-2 rounded-full shrink-0"
                      :class="getStatusDotClass(item.status)"
                    />
                    <span class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Statut actif / inactif -->
              <UBadge
                :color="item.enabled ? 'success' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ item.enabled ? 'Activé' : 'Désactivé' }}
              </UBadge>
            </div>

            <!-- Titre et infos -->
            <div>
              <h3
                class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer transition-colors"
                @click="goToShow(item)"
              >
                {{ item.name || 'Intégration sans nom' }}
              </h3>
              <p v-if="item.statusMessage" class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 line-clamp-2">
                {{ item.statusMessage }}
              </p>
              <p v-else class="text-xs text-neutral-400 dark:text-neutral-500 mt-1 italic">
                Aucun message de statut
              </p>
            </div>

            <!-- Paramètres / Serveur associé -->
            <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800/80 flex flex-col gap-1 text-xs text-neutral-500">
              <div v-if="item.server" class="flex items-center gap-1.5 truncate">
                <UIcon name="i-heroicons-server" class="size-3.5 shrink-0 text-neutral-400" />
                <span class="truncate">Serveur : {{ typeof item.server === 'object' ? (item.server.name || item.server['@id']) : item.server }}</span>
              </div>
              <div v-if="item.configuration?.url" class="flex items-center gap-1.5 truncate">
                <UIcon name="i-heroicons-globe-alt" class="size-3.5 shrink-0 text-neutral-400" />
                <span class="truncate font-mono text-[11px]">{{ item.configuration.url }}</span>
              </div>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="text-[11px] text-neutral-400 truncate">
                {{ item.lastCheckedAt ? formatDateTime(item.lastCheckedAt) : 'Jamais vérifié' }}
              </span>

              <div class="flex items-center gap-1">
                <UButton
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-bolt"
                  :loading="testingId === item['@id']"
                  aria-label="Tester la connexion"
                  title="Tester la connectivité"
                  @click="runQuickTest(item)"
                />
                <UButton
                  :to="`/integrations/${getIdFromIri(item['@id']) || item.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir les détails"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier l'intégration"
                  @click="openEditModal(item)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer l'intégration"
                  @click="handleDelete(item)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau (Composant IntegrationList généré) -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <IntegrationList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouvelle intégration">
      <template #body>
        <IntegrationCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'intégration">
      <template #body>
        <IntegrationUpdate
          v-if="selectedIntegration"
          :id="selectedIntegrationId"
          :item="selectedIntegration"
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
import type { Integration } from "~/types/integration";
import { useIntegrationListStore } from "~/stores/integration/list";
import { useIntegrationDeleteStore } from "~/stores/integration/delete";
import { useIntegrationTestStore } from "~/stores/integration/test";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import { formatDateTime } from "~/utils/date";

import IntegrationList from "~/components/integration/IntegrationList.vue";
import IntegrationCreate from "~/components/integration/IntegrationCreate.vue";
import IntegrationUpdate from "~/components/integration/IntegrationUpdate.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Intégrations - Project Manager",
});

const integrationListStore = useIntegrationListStore();
const integrationDeleteStore = useIntegrationDeleteStore();
const integrationTestStore = useIntegrationTestStore();

const { items, isLoading, error } = storeToRefs(integrationListStore);

// Synchronisation Mercure
useMercureList({
  store: integrationListStore,
  deleteStore: integrationDeleteStore,
});

async function loadIntegrations() {
  const data = await useFetchList<Integration>("integrations");
  integrationListStore.setData(data);
}
await loadIntegrations();

// État local de la vue
const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");
const testingId = ref<string | null>(null);
const quickTestNotification = ref<{ success: boolean; title: string } | null>(null);

function getIntegrationIcon(type?: string): string {
  switch (type?.toLowerCase()) {
    case "jenkins":
      return "i-heroicons-cpu-chip";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    case "nexus":
      return "i-heroicons-cube";
    default:
      return "i-heroicons-code-bracket";
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
      return "Non testé";
  }
}

function getStatusDotClass(status?: string): string {
  switch (status?.toLowerCase()) {
    case "healthy":
      return "bg-emerald-500";
    case "error":
      return "bg-rose-500";
    case "warning":
      return "bg-amber-500";
    default:
      return "bg-neutral-400";
  }
}

function getTypeColorClass(type?: string): string {
  switch (type?.toLowerCase()) {
    case "jenkins":
      return "bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300";
    case "mantis":
      return "bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300";
    case "sonarqube":
      return "bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300";
    case "nexus":
      return "bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300";
    default:
      return "bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300";
  }
}

// Modales
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedIntegration = ref<Integration | null>(null);

const selectedIntegrationId = computed(() => {
  if (!selectedIntegration.value) return undefined;
  return (
    getIdFromIri(selectedIntegration.value["@id"]) ||
    String(selectedIntegration.value.id || "")
  );
});

// Navigation vers la page Show
function goToShow(item: Integration) {
  const id = getIdFromIri(item["@id"]) || item.id;
  if (id) {
    navigateTo(`/integrations/${id}`);
  }
}

// Filtrage réactif
const filteredIntegrations = computed(() => {
  const list = items.value || [];
  if (!searchQuery.value.trim()) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter((item: Integration) => {
    const nameMatch = item.name?.toLowerCase().includes(q);
    const typeMatch = item.type?.toLowerCase().includes(q);
    const urlMatch = item.configuration?.url?.toLowerCase().includes(q);
    return Boolean(nameMatch || typeMatch || urlMatch);
  });
});

function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(item: Integration) {
  selectedIntegration.value = item;
  isEditModalOpen.value = true;
}

async function onCreated(created: Integration) {
  isCreateModalOpen.value = false;
  await loadIntegrations();
}

function onUpdated(updated: Integration) {
  isEditModalOpen.value = false;
  integrationListStore.updateItem(updated);
}

function onDeleted(deleted: Integration) {
  isEditModalOpen.value = false;
  integrationListStore.deleteItem(deleted);
}

async function runQuickTest(item: Integration) {
  const id = getIdFromIri(item["@id"] ?? "");
  if (!id) return;

  testingId.value = item["@id"] ?? null;
  quickTestNotification.value = null;

  try {
    const res = await integrationTestStore.testExisting(id);
    integrationListStore.updateItem({
      ...item,
      status: res.status,
      statusMessage: res.statusMessage,
      lastCheckedAt: res.lastCheckedAt || new Date().toISOString(),
    });

    quickTestNotification.value = {
      success: res.success,
      title: `${item.name} : ${res.statusMessage}`,
    };
  } catch (err: any) {
    quickTestNotification.value = {
      success: false,
      title: `${item.name} : Échec du test.`,
    };
  } finally {
    testingId.value = null;
    setTimeout(() => {
      quickTestNotification.value = null;
    }, 6000);
  }
}

async function handleDelete(item: Integration) {
  if (
    confirm(
      `Êtes-vous sûr de vouloir supprimer l'intégration "${item.name || item['@id']}" ?`
    )
  ) {
    const { error: delError } = await useDeleteItem(item);
    if (!delError.value) {
      integrationListStore.deleteItem(item);
      integrationDeleteStore.setDeleted(item);
    }
  }
}
</script>
