<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="project"
      name="project"
      :error="violations?.project"
      required
      class="capitalize"
    >
      <UInput
        id="projectintegration_project"
        v-model="item.project"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="integration"
      name="integration"
      :error="violations?.integration"
      required
      class="capitalize"
    >
      <UInput
        id="projectintegration_integration"
        v-model="item.integration"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="parameters"
      name="parameters"
      :error="violations?.parameters"
      
      class="capitalize"
    >
      <UInput
        id="projectintegration_parameters"
        v-model="item.parameters"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="createdAt"
      name="createdAt"
      :error="violations?.createdAt"
      
      class="capitalize"
    >
      <UInput
        id="projectintegration_createdAt"
        v-model="item.createdAt"
        class="w-full"
        type="date"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="updatedAt"
      name="updatedAt"
      :error="violations?.updatedAt"
      
      class="capitalize"
    >
      <UInput
        id="projectintegration_updatedAt"
        v-model="item.updatedAt"
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
import type { ProjectIntegration } from "~/types/projectintegration";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: ProjectIntegration;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<ProjectIntegration>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        integration: newVal.integration?.["@id"],
                createdAt: formatDateInput(newVal.createdAt),
        updatedAt: formatDateInput(newVal.updatedAt),
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: ProjectIntegration): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
