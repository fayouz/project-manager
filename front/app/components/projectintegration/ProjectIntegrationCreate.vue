<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) ProjectIntegration
        </h3>
        <UButton
          v-if="showBack"
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour"
          @click="emit('cancel')"
        />
      </div>
    </template>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-heroicons-exclamation-triangle"
      :title="error"
      class="mb-4"
    />

    <ProjectIntegrationForm :errors="violations" @submit="create">
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
          icon="i-heroicons-plus"
          :loading="isLoading"
          label="Créer"
        />
      </template>
    </ProjectIntegrationForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import ProjectIntegrationForm from "~/components/projectintegration/ProjectIntegrationForm.vue";
import { useProjectIntegrationCreateStore } from "~/stores/projectintegration/create";
import { useCreateItem } from "~/composables/api";
import type { ProjectIntegration } from "~/types/projectintegration";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: ProjectIntegration): void;
  (e: "cancel"): void;
}>();

const projectintegrationCreateStore = useProjectIntegrationCreateStore();
const { created, isLoading, violations, error } = storeToRefs(projectintegrationCreateStore);

async function create(item: ProjectIntegration) {
  const data = await useCreateItem<ProjectIntegration>("project_integrations", item);
  projectintegrationCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  projectintegrationCreateStore.$reset();
});
</script>
