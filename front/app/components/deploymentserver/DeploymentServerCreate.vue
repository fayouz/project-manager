<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) DeploymentServer
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

    <DeploymentServerForm :errors="violations" @submit="create">
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
    </DeploymentServerForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import DeploymentServerForm from "~/components/deploymentserver/DeploymentServerForm.vue";
import { useDeploymentServerCreateStore } from "~/stores/deploymentserver/create";
import { useCreateItem } from "~/composables/api";
import type { DeploymentServer } from "~/types/deploymentserver";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: DeploymentServer): void;
  (e: "cancel"): void;
}>();

const deploymentserverCreateStore = useDeploymentServerCreateStore();
const { created, isLoading, violations, error } = storeToRefs(deploymentserverCreateStore);

async function create(item: DeploymentServer) {
  const data = await useCreateItem<DeploymentServer>("deployment_servers", item);
  deploymentserverCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  deploymentserverCreateStore.$reset();
});
</script>
