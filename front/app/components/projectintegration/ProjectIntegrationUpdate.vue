<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Modifier ProjectIntegration
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

    <ProjectIntegrationForm
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
    </ProjectIntegrationForm>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, onBeforeUnmount, watch } from "vue";
import { storeToRefs } from "pinia";
import ProjectIntegrationForm from "~/components/projectintegration/ProjectIntegrationForm.vue";
import { useProjectIntegrationUpdateStore } from "~/stores/projectintegration/update";
import { useProjectIntegrationDeleteStore } from "~/stores/projectintegration/delete";
import { useFetchItem, useUpdateItem, useDeleteItem } from "~/composables/api";
import type { ProjectIntegration } from "~/types/projectintegration";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: ProjectIntegration;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "updated", item: ProjectIntegration): void;
  (e: "deleted", item: ProjectIntegration): void;
  (e: "cancel"): void;
}>();

const projectintegrationUpdateStore = useProjectIntegrationUpdateStore();
const projectintegrationDeleteStore = useProjectIntegrationDeleteStore();
const { error: deleteError, isLoading: deleteLoading } = storeToRefs(projectintegrationDeleteStore);
const { updated, violations, isLoading, error } = storeToRefs(projectintegrationUpdateStore);

const item = ref<ProjectIntegration | undefined>(props.item);

async function loadItem() {
  if (props.item) {
    item.value = props.item;
    return;
  }
  if (!props.id) return;
  const data = await useFetchItem<ProjectIntegration>(`project_integrations/${props.id}`);
  item.value = data.retrieved.value;
  projectintegrationUpdateStore.setData(data);
}

watch(() => props.id, () => loadItem(), { immediate: true });
watch(
  () => props.item,
  (newVal) => {
    if (newVal) item.value = newVal;
  }
);

async function update(payload: ProjectIntegration) {
  if (!item.value) return;

  const data = await useUpdateItem<ProjectIntegration>(item.value, payload);
  projectintegrationUpdateStore.setUpdateData(data);

  if (data.updated.value) {
    emit("updated", data.updated.value);
  }
}

async function deleteItem() {
  if (!item.value) return;

  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { isLoading: delLoading, error: delError } = await useDeleteItem(item.value);

    if (delError.value) {
      projectintegrationDeleteStore.setError(delError.value);
      return;
    }

    projectintegrationDeleteStore.setLoading(Boolean(delLoading?.value));
    projectintegrationDeleteStore.setDeleted(item.value);
    emit("deleted", item.value);
  }
}

onBeforeUnmount(() => {
  projectintegrationUpdateStore.$reset();
  projectintegrationDeleteStore.$reset();
});
</script>
