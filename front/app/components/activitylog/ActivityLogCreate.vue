<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) ActivityLog
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

    <ActivityLogForm :errors="violations" @submit="create">
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
    </ActivityLogForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import ActivityLogForm from "~/components/activitylog/ActivityLogForm.vue";
import { useActivityLogCreateStore } from "~/stores/activitylog/create";
import { useCreateItem } from "~/composables/api";
import type { ActivityLog } from "~/types/activitylog";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: ActivityLog): void;
  (e: "cancel"): void;
}>();

const activitylogCreateStore = useActivityLogCreateStore();
const { created, isLoading, violations, error } = storeToRefs(activitylogCreateStore);

async function create(item: ActivityLog) {
  const data = await useCreateItem<ActivityLog>("activity_logs", item);
  activitylogCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  activitylogCreateStore.$reset();
});
</script>
