<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Modifier OrganisationMember
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

    <OrganisationMemberForm
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
    </OrganisationMemberForm>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, onBeforeUnmount, watch } from "vue";
import { storeToRefs } from "pinia";
import OrganisationMemberForm from "~/components/organisationmember/OrganisationMemberForm.vue";
import { useOrganisationMemberUpdateStore } from "~/stores/organisationmember/update";
import { useOrganisationMemberDeleteStore } from "~/stores/organisationmember/delete";
import { useFetchItem, useUpdateItem, useDeleteItem } from "~/composables/api";
import type { OrganisationMember } from "~/types/organisationmember";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: OrganisationMember;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "updated", item: OrganisationMember): void;
  (e: "deleted", item: OrganisationMember): void;
  (e: "cancel"): void;
}>();

const organisationmemberUpdateStore = useOrganisationMemberUpdateStore();
const organisationmemberDeleteStore = useOrganisationMemberDeleteStore();
const { error: deleteError, isLoading: deleteLoading } = storeToRefs(organisationmemberDeleteStore);
const { updated, violations, isLoading, error } = storeToRefs(organisationmemberUpdateStore);

const item = ref<OrganisationMember | undefined>(props.item);

async function loadItem() {
  if (props.item) {
    item.value = props.item;
    return;
  }
  if (!props.id) return;
  const data = await useFetchItem<OrganisationMember>(`organisation_members/${props.id}`);
  item.value = data.retrieved.value;
  organisationmemberUpdateStore.setData(data);
}

watch(() => props.id, () => loadItem(), { immediate: true });
watch(
  () => props.item,
  (newVal) => {
    if (newVal) item.value = newVal;
  }
);

async function update(payload: OrganisationMember) {
  if (!item.value) return;

  const data = await useUpdateItem<OrganisationMember>(item.value, payload);
  organisationmemberUpdateStore.setUpdateData(data);

  if (data.updated.value) {
    emit("updated", data.updated.value);
  }
}

async function deleteItem() {
  if (!item.value) return;

  if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
    const { isLoading: delLoading, error: delError } = await useDeleteItem(item.value);

    if (delError.value) {
      organisationmemberDeleteStore.setError(delError.value);
      return;
    }

    organisationmemberDeleteStore.setLoading(Boolean(delLoading?.value));
    organisationmemberDeleteStore.setDeleted(item.value);
    emit("deleted", item.value);
  }
}

onBeforeUnmount(() => {
  organisationmemberUpdateStore.$reset();
  organisationmemberDeleteStore.$reset();
});
</script>
