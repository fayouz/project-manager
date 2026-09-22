<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Navbar du Dashboard -->
    <UDashboardNavbar title="Serveurs">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Serveurs</span>
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
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Serveurs d'infrastructure</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Supervisez et configurez vos serveurs (Jenkins, Gitea, SonarQube, LDAP, proxies, etc.).
          </p>
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
        <span class="text-sm text-neutral-500">Chargement des serveurs...</span>
      </div>

      <!-- Aucun serveur trouvé -->
      <div v-else-if="filteredServers.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-server" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun serveur configuré' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500 max-w-sm mx-auto">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Ajoutez votre premier serveur pour y associer des intégrations et services externes.' }}
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

      <!-- Vue Grille (Cartes personnalisées) -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <UCard
          v-for="server in filteredServers"
          :key="server['@id'] || server.id"
          class="flex flex-col justify-between hover:shadow-md transition-shadow"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 flex items-center justify-center shrink-0">
                  <UIcon name="i-heroicons-server" class="w-5 h-5" />
                </div>
                <div>
                  <h3
                    class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer transition-colors"
                    @click="goToShow(server)"
                  >
                    {{ server.name || 'Serveur sans nom' }}
                  </h3>
                  <p class="text-xs text-neutral-500 font-mono">
                    {{ server.host }}:{{ server.port }}
                  </p>
                </div>
              </div>
              <UBadge
                color="primary"
                variant="subtle"
                size="xs"
              >
                #{{ getIdFromIri(server['@id']) || server.id }}
              </UBadge>
            </div>

            <!-- Badges & Métadonnées -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <UBadge color="neutral" variant="subtle" size="xs">
                {{ getServerTypeName(server.type) }}
              </UBadge>
              <UBadge
                v-if="getAuthTypeName(server.authenticationType)"
                color="info"
                variant="subtle"
                size="xs"
              >
                {{ getAuthTypeName(server.authenticationType) }}
              </UBadge>
              <UBadge
                v-if="server.username"
                color="neutral"
                variant="outline"
                size="xs"
              >
                <UIcon name="i-heroicons-user" class="size-3 mr-1 inline" />
                {{ server.username }}
              </UBadge>
            </div>

            <!-- Options rapides -->
            <div v-if="server.options && Object.keys(server.options).length > 0" class="text-xs text-neutral-500 space-y-1">
              <div class="flex flex-wrap gap-1">
                <UBadge
                  v-for="(val, key) in server.options"
                  :key="key"
                  color="neutral"
                  variant="outline"
                  size="xs"
                >
                  {{ key }}: {{ val }}
                </UBadge>
              </div>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="font-mono text-[11px] truncate max-w-[150px]">
                {{ server['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/servers/${getIdFromIri(server['@id']) || server.id}`"
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
                  title="Modifier"
                  @click="openEditModal(server)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer le serveur"
                  title="Supprimer"
                  @click="handleDelete(server)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau (Composant ServerList généré et adapté) -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <ServerList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouveau serveur">
      <template #body>
        <ServerCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le serveur">
      <template #body>
        <ServerUpdate
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
import type { Server } from "~/types/server";
import { useServerListStore } from "~/stores/server/list";
import { useServerDeleteStore } from "~/stores/server/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import { resolveApiUrl } from "~/utils/config";
import ServerCreate from "~/components/server/ServerCreate.vue";
import ServerUpdate from "~/components/server/ServerUpdate.vue";
import ServerList from "~/components/server/ServerList.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Serveurs - Project Manager",
});

const serverListStore = useServerListStore();
const serverDeleteStore = useServerDeleteStore();
const { items, isLoading, error } = storeToRefs(serverListStore);

useMercureList({
  store: serverListStore,
  deleteStore: serverDeleteStore,
});

async function loadServers() {
  const data = await useFetchList<Server>("servers");
  serverListStore.setData(data);
}
await loadServers();

const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedServer = ref<Server | null>(null);

const selectedServerId = computed(() => {
  if (!selectedServer.value) return "";
  return String(getIdFromIri(selectedServer.value["@id"]) || selectedServer.value.id || "");
});

// Dictionnaires pour la résolution des libellés de types
const serverTypesMap = ref<Record<string, string>>({});
const authTypesMap = ref<Record<string, string>>({});

try {
  const token = useCookie<string | null>("jwt_token").value;
  const headers: Record<string, string> = {
    Accept: "application/ld+json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const [typesRes, authRes] = await Promise.all([
    $fetch<any>(resolveApiUrl("/server_types"), { headers }).catch(() => null),
    $fetch<any>(resolveApiUrl("/server_authentication_types"), { headers }).catch(() => null),
  ]);

  if (typesRes) {
    const members = typesRes.member || typesRes["hydra:member"] || [];
    members.forEach((m: any) => {
      serverTypesMap.value[m["@id"]] = m.name;
    });
  }
  if (authRes) {
    const members = authRes.member || authRes["hydra:member"] || [];
    members.forEach((m: any) => {
      authTypesMap.value[m["@id"]] = m.name;
    });
  }
} catch {
  // Non-bloquant
}

function getServerTypeName(type: any): string {
  if (!type) return "Inconnu";
  if (typeof type === "object" && type.name) return type.name;
  if (typeof type === "string") {
    return serverTypesMap.value[type] || type.split("/").pop() || type;
  }
  return String(type);
}

function getAuthTypeName(authType: any): string {
  if (!authType) return "";
  if (typeof authType === "object" && authType.name) return authType.name;
  if (typeof authType === "string") {
    return authTypesMap.value[authType] || authType.split("/").pop() || authType;
  }
  return String(authType);
}

const filteredServers = computed(() => {
  if (!items.value) return [];
  if (!searchQuery.value) return items.value;
  const q = searchQuery.value.toLowerCase();
  return items.value.filter((s) => {
    const typeName = getServerTypeName(s.type).toLowerCase();
    const authName = getAuthTypeName(s.authenticationType).toLowerCase();
    return (
      s.name?.toLowerCase().includes(q) ||
      s.host?.toLowerCase().includes(q) ||
      String(s.port).includes(q) ||
      s.username?.toLowerCase().includes(q) ||
      typeName.includes(q) ||
      authName.includes(q)
    );
  });
});

function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(server: Server) {
  selectedServer.value = server;
  isEditModalOpen.value = true;
}

function goToShow(server: Server) {
  const id = getIdFromIri(server["@id"]) || server.id;
  navigateTo(`/servers/${id}`);
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

async function handleDelete(server: Server) {
  const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer le serveur "${server.name || 'Sélectionné'}" ?`);
  if (!confirmed) return;

  const { error: delError } = await useDeleteItem(server);
  if (!delError.value) {
    serverListStore.deleteItem(server);
    serverDeleteStore.setDeleted(server);
    await loadServers();
  }
}
</script>
