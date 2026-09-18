<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) Team
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

    <TeamForm :errors="violations" @submit="create">
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
    </TeamForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import TeamForm from "~/components/team/TeamForm.vue";
import { useTeamCreateStore } from "~/stores/team/create";
import { useCreateItem } from "~/composables/api";
import type { Team } from "~/types/team";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: Team): void;
  (e: "cancel"): void;
}>();

const teamCreateStore = useTeamCreateStore();
const { created, isLoading, violations, error } = storeToRefs(teamCreateStore);

async function create(item: Team) {
  const data = await useCreateItem<Team>("teams", item);
  teamCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  teamCreateStore.$reset();
});
</script>
