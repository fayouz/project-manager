<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) TeamMember
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

    <TeamMemberForm :errors="violations" @submit="create">
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
    </TeamMemberForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import TeamMemberForm from "~/components/teammember/TeamMemberForm.vue";
import { useTeamMemberCreateStore } from "~/stores/teammember/create";
import { useCreateItem } from "~/composables/api";
import type { TeamMember } from "~/types/teammember";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: TeamMember): void;
  (e: "cancel"): void;
}>();

const teammemberCreateStore = useTeamMemberCreateStore();
const { created, isLoading, violations, error } = storeToRefs(teammemberCreateStore);

async function create(item: TeamMember) {
  const data = await useCreateItem<TeamMember>("team_members", item);
  teammemberCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  teammemberCreateStore.$reset();
});
</script>
