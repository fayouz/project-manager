<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Navbar du Dashboard -->
    <UDashboardNavbar title="Proxies">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Proxies</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredProxies.length }} prox{{ filteredProxies.length > 1 ? 'ies' : 'y' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouveau proxy"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Gestion des Proxies Réseau</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Configurez et supervisez les proxys HTTP/HTTPS utilisés par vos connecteurs et serveurs.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher un proxy..."
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
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des proxys'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des proxies...</span>
      </div>

      <!-- Aucun proxy trouvé -->
      <div v-else-if="filteredProxies.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-globe-alt" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun proxy configuré' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500 max-w-sm mx-auto">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Ajoutez votre premier proxy pour l\'associer à vos serveurs et intégrations.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau proxy"
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
          v-for="proxy in filteredProxies"
          :key="proxy['@id'] || proxy.id"
          class="flex flex-col justify-between hover:shadow-md transition-shadow"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 flex items-center justify-center shrink-0">
                  <UIcon name="i-heroicons-globe-alt" class="w-5 h-5" />
                </div>
                <div>
                  <h3
                    class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer transition-colors"
                    @click="goToShow(proxy)"
                  >
                    {{ proxy.name || 'Proxy sans nom' }}
                  </h3>
                  <p class="text-xs text-neutral-500 font-mono truncate max-w-[200px]" :title="proxy.url">
                    {{ proxy.url }}
                  </p>
                </div>
              </div>
              <UBadge
                color="primary"
                variant="subtle"
                size="xs"
              >
                #{{ getIdFromIri(proxy['@id']) || proxy.id }}
              </UBadge>
            </div>

            <!-- Badges & Métadonnées -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <UBadge
                :color="proxy.enabled ? 'success' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ proxy.enabled ? 'Actif' : 'Inactif' }}
              </UBadge>
              <UBadge
                v-if="proxy.username"
                color="neutral"
                variant="outline"
                size="xs"
              >
                <UIcon name="i-heroicons-user" class="size-3 mr-1 inline" />
                {{ proxy.username }}
              </UBadge>
              <UBadge
                v-if="proxy.noProxy"
                color="neutral"
                variant="subtle"
                size="xs"
                :title="`Exclusions: ${proxy.noProxy}`"
              >
                <UIcon name="i-heroicons-shield-check" class="size-3 mr-1 inline" />
                {{ proxy.noProxy.split(',').length }} exclusion(s)
              </UBadge>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="font-mono text-[11px] truncate max-w-[150px]">
                {{ proxy['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/proxies/${getIdFromIri(proxy['@id']) || proxy.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir le proxy"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier le proxy"
                  title="Modifier"
                  @click="openEditModal(proxy)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer le proxy"
                  title="Supprimer"
                  @click="handleDelete(proxy)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <ProxyList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouveau proxy">
      <template #body>
        <ProxyCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le proxy">
      <template #body>
        <ProxyUpdate
          v-if="selectedProxy"
          :id="selectedProxyId"
          :item="selectedProxy"
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
import type { Proxy } from "~/types/proxy";
import { useProxyListStore } from "~/stores/proxy/list";
import { useProxyDeleteStore } from "~/stores/proxy/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import ProxyCreate from "~/components/proxy/ProxyCreate.vue";
import ProxyUpdate from "~/components/proxy/ProxyUpdate.vue";
import ProxyList from "~/components/proxy/ProxyList.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Proxies - Project Manager",
});

const proxyListStore = useProxyListStore();
const proxyDeleteStore = useProxyDeleteStore();
const { items, isLoading, error } = storeToRefs(proxyListStore);

useMercureList({
  store: proxyListStore,
  deleteStore: proxyDeleteStore,
});

async function loadProxies() {
  const data = await useFetchList<Proxy>("proxies");
  proxyListStore.setData(data);
}
await loadProxies();

const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedProxy = ref<Proxy | null>(null);

const selectedProxyId = computed(() => {
  if (!selectedProxy.value) return "";
  return String(getIdFromIri(selectedProxy.value["@id"]) || selectedProxy.value.id || "");
});

const filteredProxies = computed(() => {
  if (!items.value) return [];
  if (!searchQuery.value.trim()) return items.value;

  const q = searchQuery.value.toLowerCase().trim();
  return items.value.filter((p: Proxy) => {
    return (
      (p.name && p.name.toLowerCase().includes(q)) ||
      (p.url && p.url.toLowerCase().includes(q)) ||
      (p.username && p.username.toLowerCase().includes(q)) ||
      (p.noProxy && p.noProxy.toLowerCase().includes(q))
    );
  });
});

function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(proxy: Proxy) {
  selectedProxy.value = proxy;
  isEditModalOpen.value = true;
}

function goToShow(proxy: Proxy) {
  const id = getIdFromIri(proxy["@id"]) || proxy.id;
  navigateTo(`/proxies/${id}`);
}

async function handleDelete(proxy: Proxy) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le proxy "${proxy.name}" ?`)) {
    const { error: delError } = await useDeleteItem(proxy);
    if (!delError.value) {
      proxyDeleteStore.setDeleted(proxy);
      await loadProxies();
    }
  }
}

async function onCreated() {
  isCreateModalOpen.value = false;
  await loadProxies();
}

async function onUpdated() {
  isEditModalOpen.value = false;
  selectedProxy.value = null;
  await loadProxies();
}

async function onDeleted() {
  isEditModalOpen.value = false;
  selectedProxy.value = null;
  await loadProxies();
}
</script>
