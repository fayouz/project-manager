<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Serveurs de déploiement">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Serveurs de déploiement</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredServers.length }} serveur{{ filteredServers.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouveau serveur"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Liste des serveurs de déploiement</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">Gérez les serveurs hébergeant vos environnements de recette et de production.</p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher un serveur..."
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
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des serveurs'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des serveurs de déploiement...</span>
      </div>

      <!-- Aucun serveur trouvé -->
      <div v-else-if="filteredServers.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-server-stack" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun serveur de déploiement pour le moment' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Créez votre premier serveur de déploiement pour héberger vos environnements Staging.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau serveur"
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
          v-for="server in filteredServers"
          :key="server['@id'] || server.id"
          class="flex flex-col justify-between hover:border-primary/50 transition-colors shadow-sm"
          :ui="{ body: 'p-5 space-y-3 flex-1 flex flex-col justify-between' }"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                  <UIcon name="i-heroicons-server-stack" class="size-6" />
                </div>
                <div>
                  <h3 class="font-bold text-base text-neutral-900 dark:text-neutral-100">
                    {{ server.name || 'Serveur' }}
                  </h3>
                  <p v-if="server.host" class="text-xs text-neutral-500 font-mono">
                    {{ server.host }}{{ server.port ? `:${server.port}` : '' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Webserver URL -->
            <div class="p-2.5 rounded-lg bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200 dark:border-neutral-700/60 space-y-1">
              <p class="text-[11px] font-medium text-neutral-500">URL du serveur web</p>
              <div class="flex items-center justify-between gap-2">
                <span v-if="server.webserverUrl" class="text-xs font-semibold text-primary truncate">
                  {{ server.webserverUrl }}
                </span>
                <span v-else class="text-xs text-neutral-400 italic">Non définie</span>

                <UButton
                  v-if="server.webserverUrl"
                  :to="server.webserverUrl"
                  target="_blank"
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-arrow-top-right-on-square"
                />
              </div>
            </div>

            <p v-if="server.description" class="text-xs text-neutral-600 dark:text-neutral-400 line-clamp-2">
              {{ server.description }}
            </p>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="flex items-center gap-1 font-mono">
                {{ server['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/deploymentservers/${getIdFromIri(server['@id']) || server.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir le serveur"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier le serveur"
                  @click="openEditModal(server)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer le serveur"
                  @click="handleDelete(server)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <DeploymentServerList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouveau serveur de déploiement">
      <template #body>
        <DeploymentServerCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le serveur de déploiement">
      <template #body>
        <DeploymentServerUpdate
          v-if="selectedServer"
          :id="selectedServerId"
          :item="selectedServer"
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
import type { DeploymentServer } from "~/types/deploymentserver";
import { useDeploymentServerListStore } from "~/stores/deploymentserver/list";
import { useDeploymentServerDeleteStore } from "~/stores/deploymentserver/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import DeploymentServerCreate from "~/components/deploymentserver/DeploymentServerCreate.vue";
import DeploymentServerUpdate from "~/components/deploymentserver/DeploymentServerUpdate.vue";
import DeploymentServerList from "~/components/deploymentserver/DeploymentServerList.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Serveurs de déploiement - Project Manager",
});

const serverListStore = useDeploymentServerListStore();
const serverDeleteStore = useDeploymentServerDeleteStore();
const { items, isLoading, error } = storeToRefs(serverListStore);

useMercureList({
  store: serverListStore,
  deleteStore: serverDeleteStore,
});

async function loadServers() {
  const data = await useFetchList<DeploymentServer>("deployment_servers");
  serverListStore.setData(data);
}
await loadServers();

const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedServer = ref<DeploymentServer | null>(null);

const selectedServerId = computed(() => {
  if (!selectedServer.value) return "";
  return String(getIdFromIri(selectedServer.value["@id"]) || selectedServer.value.id || "");
});

const filteredServers = computed(() => {
  if (!items.value) return [];
  if (!searchQuery.value) return items.value;
  const q = searchQuery.value.toLowerCase();
  return items.value.filter((s) => {
    return (
      s.name?.toLowerCase().includes(q) ||
      s.host?.toLowerCase().includes(q) ||
      s.webserverUrl?.toLowerCase().includes(q) ||
      s.description?.toLowerCase().includes(q)
    );
  });
});

function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(server: DeploymentServer) {
  selectedServer.value = server;
  isEditModalOpen.value = true;
}

function goToShow(server: DeploymentServer) {
  const id = getIdFromIri(server["@id"]) || server.id;
  navigateTo(`/deploymentservers/${id}`);
}

async function onCreated() {
  isCreateModalOpen.value = false;
  await loadServers();
}

async function onUpdated() {
  isEditModalOpen.value = false;
  selectedServer.value = null;
  await loadServers();
}

async function onDeleted() {
  isEditModalOpen.value = false;
  selectedServer.value = null;
  await loadServers();
}

async function handleDelete(server: DeploymentServer) {
  const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer le serveur "${server.name || 'Sélectionné'}" ?`);
  if (!confirmed) return;

  try {
    await useDeleteItem(server);
    await loadServers();
  } catch (err: any) {
    alert(err?.message || "Erreur lors de la suppression du serveur.");
  }
}
</script>
