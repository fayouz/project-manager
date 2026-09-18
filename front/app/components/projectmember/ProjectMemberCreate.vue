<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) ProjectMember
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

    <ProjectMemberForm :errors="violations" @submit="create">
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
    </ProjectMemberForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import ProjectMemberForm from "~/components/projectmember/ProjectMemberForm.vue";
import { useProjectMemberCreateStore } from "~/stores/projectmember/create";
import { useCreateItem } from "~/composables/api";
import type { ProjectMember } from "~/types/projectmember";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: ProjectMember): void;
  (e: "cancel"): void;
}>();

const projectmemberCreateStore = useProjectMemberCreateStore();
const { created, isLoading, violations, error } = storeToRefs(projectmemberCreateStore);

async function create(item: ProjectMember) {
  const data = await useCreateItem<ProjectMember>("project_members", item);
  projectmemberCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  projectmemberCreateStore.$reset();
});
</script>
