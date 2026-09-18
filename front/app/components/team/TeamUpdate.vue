<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Modifier Team
        </h3>
        <div class="flex items-center gap-2">
          <UButton
            v-if="showBack"
            variant="ghost"
            color="neutral"
            icon="i-heroicons-arrow-left"
            size="sm"
            label="Retour"
            @click="emit('cancel')"
          />
          <UButton
            variant="soft"
            color="error"
            icon="i-heroicons-trash"
            size="sm"
            :loading="deleteLoading"
            label="Supprimer"
            @click="deleteItem"
          />
        </div>
      </div>
    </template>

    <div v-if="isLoading || deleteLoading" class="flex justify-center p-6">
      <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
    </div>

    <UAlert
      v-if="error || deleteError"
      color="error"
      variant="subtle"
      icon="i-heroicons-exclamation-triangle"
      :title="error || deleteError"
      class="mb-4"
    />

    <UAlert
      v-if="updated"
      color="success"
      variant="subtle"
      icon="i-heroicons-check-circle"
      title="Mis à jour avec succès"
      class="mb-4"
    />

    <TeamForm
      v-if="item"
      :values="item"
      :errors="violations"
      @submit="update"
    >
      <template #actions>
        <UButton
          variant="ghost"
          color="neutral"
          label="Annuler"
          @click="emit('cancel')"
        />
        <UButton
          type="submit"
          color="primary"
          icon="i-heroicons-check"
          :loading="isLoading"
          label="Enregistrer"
        />
      </template>
    </TeamForm>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, onBeforeUnmount, watch } from "vue";
import { storeToRefs } from "pinia";
import TeamForm from "~/components/team/TeamForm.vue";
import { useTeamUpdateStore } from "~/stores/team/update";
import { useTeamDeleteStore } from "~/stores/team/delete";
import { useFetchItem, useUpdateItem, useDeleteItem } from "~/composables/api";
import type { Team } from "~/types/team";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: Team;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "updated", item: Team): void;
  (e: "deleted", item: Team): void;
  (e: "cancel"): void;
}>();

const teamUpdateStore = useTeamUpdateStore();
const teamDeleteStore = useTeamDeleteStore();
const { error: deleteError, isLoading: deleteLoading } = storeToRefs(teamDeleteStore);
const { updated, violations, isLoading, error } = storeToRefs(teamUpdateStore);

const item = ref<Team | undefined>(props.item);

async function loadItem() {
  if (props.item) {
    item.value = props.item;
    return;
  }
  if (!props.id) return;
  const data = await useFetchItem<Team>(`teams/${props.id}`);
  item.value = data.retrieved.value;
  teamUpdateStore.setData(data);
}

watch(() => props.id, () => loadItem(), { immediate: true });
watch(
  () => props.item,
  (newVal) => {
    if (newVal) item.value = newVal;
  }
);

async function update(payload: Team) {
  if (!item.value) return;

  const data = await useUpdateItem<Team>(item.value, payload);
  teamUpdateStore.setUpdateData(data);

  if (data.updated.value) {
    emit("updated", data.updated.value);
  }
}

async function deleteItem() {
  if (!item.value) return;

  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { isLoading: delLoading, error: delError } = await useDeleteItem(item.value);

    if (delError.value) {
      teamDeleteStore.setError(delError.value);
      return;
    }

    teamDeleteStore.setLoading(Boolean(delLoading?.value));
    teamDeleteStore.setDeleted(item.value);
    emit("deleted", item.value);
  }
}

onBeforeUnmount(() => {
  teamUpdateStore.$reset();
  teamDeleteStore.$reset();
});
</script>
