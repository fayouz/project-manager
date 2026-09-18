<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="organisation"
      name="organisation"
      :error="violations?.organisation"
      required
      class="capitalize"
    >
      <UInput
        id="organisationmember_organisation"
        v-model="item.organisation"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="user"
      name="user"
      :error="violations?.user"
      required
      class="capitalize"
    >
      <UInput
        id="organisationmember_user"
        v-model="item.user"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="role"
      name="role"
      :error="violations?.role"
      required
      class="capitalize"
    >
      <UInput
        id="organisationmember_role"
        v-model="item.role"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="joinedAt"
      name="joinedAt"
      :error="violations?.joinedAt"
      
      class="capitalize"
    >
      <UInput
        id="organisationmember_joinedAt"
        v-model="item.joinedAt"
        class="w-full"
        type="date"
        placeholder=""
      />
    </UFormField>

    <div class="flex items-center justify-end gap-3 pt-2">
      <slot name="actions">
        <UButton
          type="submit"
          color="primary"
          icon="i-heroicons-check"
          label="Enregistrer"
        />
      </slot>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { ref, toRef, watch } from "vue";
import type { OrganisationMember } from "~/types/organisationmember";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: OrganisationMember;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<OrganisationMember>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        user: newVal.user?.["@id"],
                joinedAt: formatDateInput(newVal.joinedAt),
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: OrganisationMember): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
