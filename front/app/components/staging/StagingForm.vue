<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="name"
      name="name"
      :error="violations?.name"
      
      class="capitalize"
    >
      <UInput
        id="staging_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="project"
      name="project"
      :error="violations?.project"
      
      class="capitalize"
    >
      <UInput
        id="staging_project"
        v-model="item.project"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="deploymentServer"
      name="deploymentServer"
      :error="violations?.deploymentServer"
      
      class="capitalize"
    >
      <UInput
        id="staging_deploymentServer"
        v-model="item.deploymentServer"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="environment"
      name="environment"
      :error="violations?.environment"
      
      class="capitalize"
    >
      <UInput
        id="staging_environment"
        v-model="item.environment"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="status"
      name="status"
      :error="violations?.status"
      
      class="capitalize"
    >
      <UInput
        id="staging_status"
        v-model="item.status"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="branch"
      name="branch"
      :error="violations?.branch"
      
      class="capitalize"
    >
      <UInput
        id="staging_branch"
        v-model="item.branch"
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
        id="staging_description"
        v-model="item.description"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="url"
      name="url"
      :error="violations?.url"
      description="URL permettant de produire un aperçu de la page web (facultatif, indépendant de la branche git)"
      class="capitalize"
    >
      <UInput
        id="staging_url"
        v-model="item.url"
        class="w-full"
        type="text"
        placeholder="https://staging.example.com"
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
import type { Staging } from "~/types/staging";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: Staging;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Staging>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: Staging): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
