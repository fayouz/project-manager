<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) Organisation
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

    <OrganisationForm :errors="violations" @submit="create">
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
    </OrganisationForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import OrganisationForm from "~/components/organisation/OrganisationForm.vue";
import { useOrganisationCreateStore } from "~/stores/organisation/create";
import { useCreateItem } from "~/composables/api";
import type { Organisation } from "~/types/organisation";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: Organisation): void;
  (e: "cancel"): void;
}>();

const organisationCreateStore = useOrganisationCreateStore();
const { created, isLoading, violations, error } = storeToRefs(organisationCreateStore);

async function create(item: Organisation) {
  const data = await useCreateItem<Organisation>("organisations", item);
  organisationCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  organisationCreateStore.$reset();
});
</script>
