<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="name"
      name="name"
      :error="violations?.name"
      
      class="capitalize"
    >
      <UInput
        id="deploymentserver_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="webserverUrl"
      name="webserverUrl"
      :error="violations?.webserverUrl"
      
      class="capitalize"
    >
      <UInput
        id="deploymentserver_webserverUrl"
        v-model="item.webserverUrl"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="host"
      name="host"
      :error="violations?.host"
      
      class="capitalize"
    >
      <UInput
        id="deploymentserver_host"
        v-model="item.host"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="port"
      name="port"
      :error="violations?.port"
      
      class="capitalize"
    >
      <UInput
        id="deploymentserver_port"
        v-model="item.port"
        class="w-full"
        type="number"
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
        id="deploymentserver_description"
        v-model="item.description"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="stagings"
      name="stagings"
      :error="violations?.stagings"
      
      class="capitalize"
    >
      <FormRepeater
        :values="item.stagings"
        @update="(values: any[]) => (item.stagings = values)"
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
import type { DeploymentServer } from "~/types/deploymentserver";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: DeploymentServer;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<DeploymentServer>({ ...props.values });

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
  (e: "submit", item: DeploymentServer): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
