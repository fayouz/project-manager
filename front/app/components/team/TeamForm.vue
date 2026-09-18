<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="name"
      name="name"
      :error="violations?.name"
      required
      class="capitalize"
    >
      <UInput
        id="team_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="description"
      name="description"
      :error="violations?.description"
      
      class="capitalize"
    >
      <UInput
        id="team_description"
        v-model="item.description"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="project"
      name="project"
      :error="violations?.project"
      required
      class="capitalize"
    >
      <UInput
        id="team_project"
        v-model="item.project"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="members"
      name="members"
      :error="violations?.members"
      
      class="capitalize"
    >
      <FormRepeater
        :values="item.members"
        @update="(values: any[]) => (item.members = values)"
      />
    </UFormField>
    <UFormField
      label="createdAt"
      name="createdAt"
      :error="violations?.createdAt"
      
      class="capitalize"
    >
      <UInput
        id="team_createdAt"
        v-model="item.createdAt"
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
import FormRepeater from "~/components/common/FormRepeater.vue";
import type { Team } from "~/types/team";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: Team;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Team>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        createdAt: formatDateInput(newVal.createdAt),
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: Team): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
