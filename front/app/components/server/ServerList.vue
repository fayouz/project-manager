<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
          <span>Serveurs</span>
          <UBadge v-if="items?.length" color="primary" variant="subtle" size="sm">
            {{ items.length }}
          </UBadge>
        </h2>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <slot name="header-actions">
          <UButton
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau serveur"
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
      title="Serveur supprimé avec succès."
    />

    <UCard :ui="{ body: 'p-0 sm:p-0' }">
      <div v-if="isLoading" class="flex justify-center items-center p-8">
        <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
        <span class="ml-2 text-sm text-neutral-500">Chargement...</span>
      </div>

      <div v-else-if="!items || items.length === 0" class="text-center py-12 px-4">
        <UIcon name="i-heroicons-server" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">Aucun serveur</h3>
        <p class="mt-1 text-sm text-neutral-500">Commencez par ajouter un premier serveur d'infrastructure.</p>
        <div class="mt-4">
          <UButton
            color="primary"
            variant="soft"
            icon="i-heroicons-plus"
            label="Ajouter un serveur"
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
                Hôte & Port
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Utilisateur
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Type
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Authentification
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Options
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
                  :to="`/servers/${getIdFromIri(item['@id']) || item.id}`"
                  class="text-primary hover:underline font-medium"
                >
                  #{{ getIdFromIri(item['@id']) || item.id }}
                </NuxtLink>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                {{ item.name }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-600 dark:text-neutral-400 font-mono">
                {{ item.host }}:{{ item.port }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                {{ item.username || '—' }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <UBadge color="neutral" variant="subtle" size="xs">
                  {{ getServerTypeName(item.type) }}
                </UBadge>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <UBadge
                  v-if="getAuthTypeName(item.authenticationType)"
                  color="primary"
                  variant="subtle"
                  size="xs"
                >
                  {{ getAuthTypeName(item.authenticationType) }}
                </UBadge>
                <span v-else class="text-xs text-neutral-400">Aucune</span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-500">
                <div v-if="item.options && Object.keys(item.options).length > 0" class="flex flex-wrap gap-1">
                  <UBadge
                    v-for="(val, key) in item.options"
                    :key="key"
                    color="neutral"
                    variant="outline"
                    size="xs"
                  >
                    {{ key }}: {{ val }}
                  </UBadge>
                </div>
                <span v-else class="text-xs text-neutral-400">—</span>
              </td>
              <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-1">
                  <UButton
                    :to="`/servers/${getIdFromIri(item['@id']) || item.id}`"
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
import { useServerListStore } from "~/stores/server/list";
import { useServerDeleteStore } from "~/stores/server/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { resolveApiUrl } from "~/utils/config";
import type { Server } from "~/types/server";

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", item: Server): void;
  (e: "show", item: Server): void;
  (e: "deleted", item: Server): void;
}>();

const serverListStore = useServerListStore();
const serverDeleteStore = useServerDeleteStore();

const { items, isLoading, error } = storeToRefs(serverListStore);
const { deleted: deletedItem } = storeToRefs(serverDeleteStore);

const data = await useFetchList<Server>("servers");
serverListStore.setData(data);

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

async function handleDelete(item: Server) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le serveur "${item.name || 'Sélectionné'}" ?`)) {
    const { error: delError } = await useDeleteItem(item);
    if (!delError.value) {
      serverListStore.deleteItem(item);
      serverDeleteStore.setDeleted(item);
      emit("deleted", item);
    }
  }
}

onBeforeUnmount(() => {
  serverListStore.$reset();
  serverDeleteStore.$reset();
});
</script>
