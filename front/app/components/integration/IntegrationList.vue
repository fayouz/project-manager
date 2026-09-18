<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
          <span>Intégrations</span>
          <UBadge v-if="items?.length" color="neutral" variant="subtle" size="sm">
            {{ items.length }}
          </UBadge>
        </h2>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <slot name="header-actions">
          <UButton
            color="primary"
            icon="i-heroicons-plus"
            label="Nouvelle intégration"
            @click="emit('create')"
          />
        </slot>
      </div>
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-heroicons-exclamation-triangle"
      :title="typeof error === 'string' ? error : error?.message || 'Une erreur est survenue'"
    />

    <UAlert
      v-if="deletedItem"
      color="success"
      variant="subtle"
      icon="i-heroicons-check-circle"
      title="Intégration supprimée avec succès."
    />

    <UCard :ui="{ body: 'p-0 sm:p-0' }">
      <div v-if="isLoading" class="flex justify-center items-center p-8">
        <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
        <span class="ml-2 text-sm text-neutral-500">Chargement...</span>
      </div>

      <div v-else-if="!items || items.length === 0" class="text-center py-12 px-4">
        <UIcon name="i-heroicons-puzzle-piece" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">Aucune intégration</h3>
        <p class="mt-1 text-sm text-neutral-500">Connectez vos serveurs d'intégration continue ou forges logicielles.</p>
        <div class="mt-4">
          <UButton
            color="primary"
            variant="soft"
            icon="i-heroicons-plus"
            label="Ajouter une intégration"
            @click="emit('create')"
          />
        </div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
          <thead class="bg-neutral-50 dark:bg-neutral-900/50">
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                ID
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Nom
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Type
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Serveur lié
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Santé
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Statut
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Dernier test
              </th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800 bg-white dark:bg-neutral-900">
            <tr
              v-for="item in items"
              :key="item['@id']"
              class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors"
            >
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-mono text-neutral-500">
                <NuxtLink
                  :to="`/integrations/${getIdFromIri(item['@id'])}`"
                  class="text-primary hover:underline font-medium"
                >
                  {{ getIdFromIri(item['@id']) }}
                </NuxtLink>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                {{ item.name }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-600 dark:text-neutral-300">
                <div class="flex items-center gap-1.5">
                  <UIcon
                    :name="getIntegrationIcon(item.type)"
                    class="size-4 text-primary"
                  />
                  <span class="capitalize">{{ item.type }}</span>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-500">
                <UBadge v-if="item.server" color="neutral" variant="outline" size="xs">
                  {{ typeof item.server === 'object' ? item.server.name || item.server['@id'] : item.server }}
                </UBadge>
                <span v-else class="text-xs text-neutral-400">-</span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <UBadge
                  :color="item.status === 'healthy' ? 'success' : item.status === 'error' ? 'error' : 'neutral'"
                  variant="subtle"
                  size="xs"
                >
                  {{ item.status === 'healthy' ? 'Opérationnel' : item.status === 'error' ? 'Erreur' : 'Non testé' }}
                </UBadge>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <UBadge
                  :color="item.enabled ? 'success' : 'neutral'"
                  variant="soft"
                  size="xs"
                >
                  {{ item.enabled ? 'Actif' : 'Inactif' }}
                </UBadge>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-xs text-neutral-500">
                {{ item.lastCheckedAt ? formatDateTime(item.lastCheckedAt) : '-' }}
              </td>
              <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-1">
                  <UButton
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-heroicons-bolt"
                    :loading="testingId === item['@id']"
                    aria-label="Tester"
                    @click="handleTest(item)"
                  />
                  <UButton
                    :to="`/integrations/${getIdFromIri(item['@id'])}`"
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-heroicons-eye"
                    aria-label="Voir"
                    @click="emit('show', item)"
                  />
                  <UButton
                    variant="ghost"
                    color="primary"
                    size="xs"
                    icon="i-heroicons-pencil-square"
                    aria-label="Modifier"
                    @click="emit('edit', item)"
                  />
                  <UButton
                    variant="ghost"
                    color="error"
                    size="xs"
                    icon="i-heroicons-trash"
                    aria-label="Supprimer"
                    @click="handleDelete(item)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UCard>
  </div>
</template>

<script lang="ts" setup>
import { ref, onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import { useIntegrationListStore } from "~/stores/integration/list";
import { useIntegrationDeleteStore } from "~/stores/integration/delete";
import { useIntegrationTestStore } from "~/stores/integration/test";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { formatDateTime } from "~/utils/date";
import type { Integration } from "~/types/integration";

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", item: Integration): void;
  (e: "show", item: Integration): void;
  (e: "deleted", item: Integration): void;
}>();

const integrationListStore = useIntegrationListStore();
const integrationDeleteStore = useIntegrationDeleteStore();
const integrationTestStore = useIntegrationTestStore();

const { items, isLoading, error } = storeToRefs(integrationListStore);
const { deleted: deletedItem } = storeToRefs(integrationDeleteStore);

const testingId = ref<string | null>(null);

function getIntegrationIcon(type?: string): string {
  switch (type?.toLowerCase()) {
    case "jenkins":
      return "i-heroicons-cpu-chip";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    default:
      return "i-heroicons-code-bracket";
  }
}

const data = await useFetchList<Integration>("integrations");
integrationListStore.setData(data);

async function handleTest(item: Integration) {
  const id = getIdFromIri(item["@id"] ?? "");
  if (!id) return;

  testingId.value = item["@id"] ?? null;
  try {
    const res = await integrationTestStore.testExisting(id);
    integrationListStore.updateItem({
      ...item,
      status: res.status,
      statusMessage: res.statusMessage,
      lastCheckedAt: res.lastCheckedAt || new Date().toISOString(),
    });
  } finally {
    testingId.value = null;
  }
}

async function handleDelete(item: Integration) {
  if (confirm("Êtes-vous sûr de vouloir supprimer cette intégration ?")) {
    const { error: delError } = await useDeleteItem(item);
    if (!delError.value) {
      integrationListStore.deleteItem(item);
      integrationDeleteStore.setDeleted(item);
      emit("deleted", item);
    }
  }
}

onBeforeUnmount(() => {
  integrationListStore.$reset();
  integrationDeleteStore.$reset();
});
</script>
