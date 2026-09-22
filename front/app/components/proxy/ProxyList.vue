<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
          <span>Proxies</span>
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
            label="Nouveau"
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
      title="Élément supprimé avec succès."
    />

    <UCard :ui="{ body: 'p-0 sm:p-0' }">
      <div v-if="isLoading" class="flex justify-center items-center p-8">
        <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
        <span class="ml-2 text-sm text-neutral-500">Chargement...</span>
      </div>

      <div v-else-if="!items || items.length === 0" class="text-center py-12 px-4">
        <UIcon name="i-heroicons-globe-alt" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">Aucun proxy configuré</h3>
        <p class="mt-1 text-sm text-neutral-500">Commencez par en ajouter un pour vos serveurs et intégrations.</p>
        <div class="mt-4">
          <UButton
            color="primary"
            variant="soft"
            icon="i-heroicons-plus"
            label="Ajouter un proxy"
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
                URL
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Utilisateur
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Exclusions (noProxy)
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Statut
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Créé le
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
                  :to="`/proxies/${getIdFromIri(item['@id'])}`"
                  class="text-primary hover:underline font-medium"
                >
                  {{ getIdFromIri(item['@id']) }}
                </NuxtLink>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                <span class="cursor-pointer hover:text-primary transition-colors" @click="emit('show', item)">
                  {{ item.name }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-neutral-600 dark:text-neutral-300">
                {{ item.url }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-500">
                {{ item.username || '—' }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-500 max-w-[200px] truncate" :title="item.noProxy">
                {{ item.noProxy || '—' }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <UBadge :color="item.enabled ? 'success' : 'neutral'" variant="subtle" size="xs">
                  {{ item.enabled ? 'Actif' : 'Inactif' }}
                </UBadge>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-500">
                {{ item.createdAt ? formatDateTime(item.createdAt) : '—' }}
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-1">
                  <UButton
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-heroicons-eye"
                    aria-label="Voir"
                    title="Voir"
                    @click="emit('show', item)"
                  />
                  <UButton
                    variant="ghost"
                    color="primary"
                    size="xs"
                    icon="i-heroicons-pencil-square"
                    aria-label="Modifier"
                    title="Modifier"
                    @click="emit('edit', item)"
                  />
                  <UButton
                    variant="ghost"
                    color="error"
                    size="xs"
                    icon="i-heroicons-trash"
                    aria-label="Supprimer"
                    title="Supprimer"
                    @click="deleteItem(item)"
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
import { storeToRefs } from "pinia";
import { useProxyListStore } from "~/stores/proxy/list";
import { useProxyDeleteStore } from "~/stores/proxy/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { formatDateTime } from "~/utils/date";
import type { Proxy } from "~/types/proxy";

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", item: Proxy): void;
  (e: "show", item: Proxy): void;
  (e: "deleted", item: Proxy): void;
}>();

const proxyListStore = useProxyListStore();
const proxyDeleteStore = useProxyDeleteStore();

const { items, isLoading, error } = storeToRefs(proxyListStore);
const { deleted: deletedItem } = storeToRefs(proxyDeleteStore);

async function load() {
  const data = await useFetchList<Proxy>("proxies");
  proxyListStore.setData(data);
}

async function deleteItem(item: Proxy) {
  if (confirm("Êtes-vous sûr de vouloir supprimer ce proxy ?")) {
    const { isLoading: delLoading, error: delError } = await useDeleteItem(item);

    if (delError.value) {
      proxyDeleteStore.setError(delError.value);
      return;
    }

    proxyDeleteStore.setLoading(Boolean(delLoading?.value));
    proxyDeleteStore.setDeleted(item);
    emit("deleted", item);
    await load();
  }
}

await load();
</script>
