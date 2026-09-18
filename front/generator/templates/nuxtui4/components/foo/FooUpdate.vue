<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Modifier {{titleUcFirst}}
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

    <{{titleUcFirst}}Form
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
    </{{titleUcFirst}}Form>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, onBeforeUnmount, watch } from "vue";
import { storeToRefs } from "pinia";
import {{titleUcFirst}}Form from "~/components/{{lc}}/{{titleUcFirst}}Form.vue";
import { use{{titleUcFirst}}UpdateStore } from "~/stores/{{lc}}/update";
import { use{{titleUcFirst}}DeleteStore } from "~/stores/{{lc}}/delete";
import { useFetchItem, useUpdateItem, useDeleteItem } from "~/composables/api";
import type { {{titleUcFirst}} } from "~/types/{{lc}}";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: {{titleUcFirst}};
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "updated", item: {{titleUcFirst}}): void;
  (e: "deleted", item: {{titleUcFirst}}): void;
  (e: "cancel"): void;
}>();

const {{lc}}UpdateStore = use{{titleUcFirst}}UpdateStore();
const {{lc}}DeleteStore = use{{titleUcFirst}}DeleteStore();
const { error: deleteError, isLoading: deleteLoading } = storeToRefs({{lc}}DeleteStore);
const { updated, violations, isLoading, error } = storeToRefs({{lc}}UpdateStore);

const item = ref<{{titleUcFirst}} | undefined>(props.item);

async function loadItem() {
  if (props.item) {
    item.value = props.item;
    return;
  }
  if (!props.id) return;
  const data = await useFetchItem<{{titleUcFirst}}>(`{{name}}/${props.id}`);
  item.value = data.retrieved.value;
  {{lc}}UpdateStore.setData(data);
}

watch(() => props.id, () => loadItem(), { immediate: true });
watch(
  () => props.item,
  (newVal) => {
    if (newVal) item.value = newVal;
  }
);

async function update(payload: {{titleUcFirst}}) {
  if (!item.value) return;

  const data = await useUpdateItem<{{titleUcFirst}}>(item.value, payload);
  {{lc}}UpdateStore.setUpdateData(data);

  if (data.updated.value) {
    emit("updated", data.updated.value);
  }
}

async function deleteItem() {
  if (!item.value) return;

  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { isLoading: delLoading, error: delError } = await useDeleteItem(item.value);

    if (delError.value) {
      {{lc}}DeleteStore.setError(delError.value);
      return;
    }

    {{lc}}DeleteStore.setLoading(Boolean(delLoading?.value));
    {{lc}}DeleteStore.setDeleted(item.value);
    emit("deleted", item.value);
  }
}

onBeforeUnmount(() => {
  {{lc}}UpdateStore.$reset();
  {{lc}}DeleteStore.$reset();
});
</script>
