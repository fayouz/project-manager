<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Créer un(e) OrganisationMember
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

    <OrganisationMemberForm :errors="violations" @submit="create">
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
    </OrganisationMemberForm>
  </UCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from "vue";
import { storeToRefs } from "pinia";
import OrganisationMemberForm from "~/components/organisationmember/OrganisationMemberForm.vue";
import { useOrganisationMemberCreateStore } from "~/stores/organisationmember/create";
import { useCreateItem } from "~/composables/api";
import type { OrganisationMember } from "~/types/organisationmember";

const props = withDefaults(
  defineProps<{
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "created", item: OrganisationMember): void;
  (e: "cancel"): void;
}>();

const organisationmemberCreateStore = useOrganisationMemberCreateStore();
const { created, isLoading, violations, error } = storeToRefs(organisationmemberCreateStore);

async function create(item: OrganisationMember) {
  const data = await useCreateItem<OrganisationMember>("organisation_members", item);
  organisationmemberCreateStore.setData(data);

  if (created?.value) {
    emit("created", created.value);
  }
}

onBeforeUnmount(() => {
  organisationmemberCreateStore.$reset();
});
</script>
