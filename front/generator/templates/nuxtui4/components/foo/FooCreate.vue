<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) {{titleUcFirst}}
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

    <{{titleUcFirst}}Form :errors="violations" @submit="create">
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
    </{{titleUcFirst}}Form>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import {{titleUcFirst}}Form from "~/components/{{lc}}/{{titleUcFirst}}Form.vue";
import { use{{titleUcFirst}}CreateStore } from "~/stores/{{lc}}/create";
import { useCreateItem } from "~/composables/api";
import type { {{titleUcFirst}} } from "~/types/{{lc}}";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: {{titleUcFirst}}): void;
  (e: "cancel"): void;
}>();

const {{lc}}CreateStore = use{{titleUcFirst}}CreateStore();
const { created, isLoading, violations, error } = storeToRefs({{lc}}CreateStore);

async function create(item: {{titleUcFirst}}) {
  const data = await useCreateItem<{{titleUcFirst}}>("{{name}}", item);
  {{lc}}CreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  {{lc}}CreateStore.$reset();
});
</script>
