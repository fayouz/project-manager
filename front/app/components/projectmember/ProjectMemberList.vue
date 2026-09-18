<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
          <span>ProjectMembers</span>
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
        <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">Aucun(e) projectmember</h3>
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
                project
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                user
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                role
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                joinedAt
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
                  :to="`/projectmembers/${getIdFromIri(item['@id'])}`"
                  class="text-primary hover:underline font-medium"
                >
                  {{ getIdFromIri(item['@id']) }}
                </NuxtLink>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                {{ item.project }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                {{ item.user }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                {{ item.role }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                {{ formatDateTime(item.joinedAt) }}
              </td>
              <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-1">
                  <UButton
                    :to="`/projectmembers/${getIdFromIri(item['@id'])}`"
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
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import { useProjectMemberListStore } from "~/stores/projectmember/list";
import { useProjectMemberDeleteStore } from "~/stores/projectmember/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { formatDateTime } from "~/utils/date";
import type { ProjectMember } from "~/types/projectmember";

const emit = defineEmits<{
  (e: "create"): void;
  (e: "edit", item: ProjectMember): void;
  (e: "show", item: ProjectMember): void;
  (e: "deleted", item: ProjectMember): void;
}>();

const projectmemberListStore = useProjectMemberListStore();
const projectmemberDeleteStore = useProjectMemberDeleteStore();

const { items, isLoading, error } = storeToRefs(projectmemberListStore);
const { deleted: deletedItem } = storeToRefs(projectmemberDeleteStore);

const data = await useFetchList<ProjectMember>("project_members");
projectmemberListStore.setData(data);

async function handleDelete(item: ProjectMember) {
  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { error: delError } = await useDeleteItem(item);
    if (!delError.value) {
      projectmemberListStore.deleteItem(item);
      projectmemberDeleteStore.setDeleted(item);
      emit("deleted", item);
    }
  }
}

onBeforeUnmount(() => {
  projectmemberListStore.$reset();
  projectmemberDeleteStore.$reset();
});
</script>
