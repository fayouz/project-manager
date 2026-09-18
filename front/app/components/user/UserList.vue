<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
          <span>Users</span>
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
        <UIcon name="i-heroicons-inbox" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">Aucun(e) user</h3>
        <p class="mt-1 text-sm text-neutral-500">Commencez par en ajouter un(e).</p>
        <div class="mt-4">
          <UButton
            color="primary"
            variant="soft"
            icon="i-heroicons-plus"
            label="Ajouter"
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
                Utilisateur
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Manager
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Rôles
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                Type
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
                  :to="`/users/${getIdFromIri(item['@id'])}`"
                  class="text-primary hover:underline font-medium"
                >
                  {{ getIdFromIri(item['@id']) }}
                </NuxtLink>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                <div class="flex items-center gap-3">
                  <UAvatar
                    :src="getUserAvatar(item)"
                    :text="getInitials(item)"
                    :alt="item.username || item.email"
                    size="sm"
                  />
                  <div>
                    <span class="font-medium block">{{ item.displayName || item.email }}</span>
                    <span class="text-xs text-neutral-500 font-mono">
                      {{ item.email }}<span v-if="item.title || item.department"> · {{ [item.title, item.department].filter(Boolean).join(' - ') }}</span>
                    </span>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                <div v-if="item.manager" class="flex items-center gap-2">
                  <UAvatar
                    :src="getManagerAvatar(item.manager)"
                    :text="getManagerInitials(item.manager)"
                    size="xs"
                  />
                  <div class="truncate max-w-[160px]">
                    <NuxtLink
                      v-if="typeof item.manager === 'object' && (item.manager['@id'] || item.manager.id)"
                      :to="`/users/${getIdFromIri(item.manager['@id']) || item.manager.id}`"
                      class="text-xs font-medium text-primary hover:underline block truncate"
                    >
                      {{ getManagerName(item.manager) }}
                    </NuxtLink>
                    <span v-else class="text-xs font-medium block truncate">
                      {{ getManagerName(item.manager) }}
                    </span>
                    <span v-if="typeof item.manager === 'object' && item.manager.title" class="text-[10px] text-neutral-400 block truncate">
                      {{ item.manager.title }}
                    </span>
                  </div>
                </div>
                <span v-else class="text-xs text-neutral-400 italic">-</span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                <div class="flex flex-wrap gap-1">
                  <UBadge
                    v-for="role in (Array.isArray(item.roles) ? item.roles : [item.roles])"
                    :key="String(role)"
                    :color="role === 'ROLE_ADMIN' ? 'primary' : 'neutral'"
                    variant="subtle"
                    size="xs"
                  >
                    {{ role }}
                  </UBadge>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                <UBadge
                  :color="item.isLdap ? 'warning' : 'neutral'"
                  variant="soft"
                  size="xs"
                >
                  {{ item.isLdap ? 'LDAP' : 'Local' }}
                </UBadge>
              </td>
              <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-1">
                  <UButton
                    v-if="item.isLdap"
                    variant="ghost"
                    color="warning"
                    size="xs"
                    icon="i-heroicons-arrow-path"
                    aria-label="Rafraîchir depuis LDAP"
                    title="Rafraîchir depuis LDAP (image, mail...)"
                    :loading="refreshingId === (getIdFromIri(item['@id']) || item.id)"
                    @click="handleRefreshLdap(item)"
                  />
                  <UButton
                    :to="`/users/${getIdFromIri(item['@id'])}`"
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
import { useUserListStore } from "~/stores/user/list";
import { useUserDeleteStore } from "~/stores/user/delete";
import { useAuthStore } from "~/stores/auth";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { getEntrypoint } from "~/utils/config";
import { formatDateTime } from "~/utils/date";
import type { User } from "~/types/user";

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", item: User): void;
  (e: "show", item: User): void;
  (e: "deleted", item: User): void;
  (e: "refreshed", item: User): void;
}>();

const userListStore = useUserListStore();
const userDeleteStore = useUserDeleteStore();
const authStore = useAuthStore();

const { items, isLoading, error } = storeToRefs(userListStore);
const { deleted: deletedItem } = storeToRefs(userDeleteStore);

const refreshingId = ref<string | number | undefined>(undefined);

function getUserAvatar(user: User): string | undefined {
  if (user.avatar) return user.avatar;
  if (user.image) {
    return user.image.startsWith("data:")
      ? user.image
      : `data:image/jpeg;base64,${user.image}`;
  }
  return undefined;
}

function getInitials(user: User): string {
  if (user.username) {
    return user.username.slice(0, 2).toUpperCase();
  }
  if (user.email) {
    return user.email.slice(0, 2).toUpperCase();
  }
  return "U";
}

function getManagerAvatar(mgr: any): string | undefined {
  if (!mgr || typeof mgr !== "object") return undefined;
  if (mgr.avatar) return mgr.avatar;
  if (mgr.image) {
    return mgr.image.startsWith("data:")
      ? mgr.image
      : `data:image/jpeg;base64,${mgr.image}`;
  }
  return undefined;
}

function getManagerInitials(mgr: any): string {
  if (!mgr || typeof mgr !== "object") return "M";
  const name = mgr.displayName || mgr.username || mgr.email || "M";
  return name.slice(0, 2).toUpperCase();
}

function getManagerName(mgr: any): string {
  if (!mgr) return "";
  if (typeof mgr !== "object") return String(mgr);
  return mgr.displayName || mgr.username || mgr.email || "Manager";
}

async function handleRefreshLdap(item: User) {
  const id = getIdFromIri(item["@id"]) || item.id;
  if (!id || refreshingId.value) return;

  refreshingId.value = id;
  try {
    const res = await $fetch<{ success: boolean; message: string; user?: User }>(
      `${getEntrypoint()}/ldap/users/${id}/refresh`,
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
      }
    );
    if (res.user) {
      userListStore.updateItem(res.user);
      emit("refreshed", res.user);
      if (authStore.user?.id === Number(id)) {
        await authStore.fetchCurrentUser();
      }
    }
  } catch (err: any) {
    console.error("Erreur lors du rafraîchissement LDAP de l'utilisateur:", err);
  } finally {
    refreshingId.value = undefined;
  }
}

const data = await useFetchList<User>("users");
userListStore.setData(data);

async function handleDelete(item: User) {
  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { error: delError } = await useDeleteItem(item);
    if (!delError.value) {
      userListStore.deleteItem(item);
      userDeleteStore.setDeleted(item);
      emit("deleted", item);
    }
  }
}

onBeforeUnmount(() => {
  userListStore.$reset();
  userDeleteStore.$reset();
});
</script>
