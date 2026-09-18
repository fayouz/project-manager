<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="name"
      name="name"
      :error="violations?.name"
      
      class="capitalize"
    >
      <UInput
        id="server_name"
        v-model="item.name"
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
        id="server_host"
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
        id="server_port"
        v-model="item.port"
        class="w-full"
        type="number"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="username"
      name="username"
      :error="violations?.username"
      
      class="capitalize"
    >
      <UInput
        id="server_username"
        v-model="item.username"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="password"
      name="password"
      :error="violations?.password"
      
      class="capitalize"
    >
      <UInput
        id="server_password"
        v-model="item.password"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="options"
      name="options"
      :error="violations?.options"
      
      class="capitalize"
    >
      <UInput
        id="server_options"
        v-model="item.options"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="type"
      name="type"
      :error="violations?.type"
      
      class="capitalize"
    >
      <UInput
        id="server_type"
        v-model="item.type"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="authenticationType"
      name="authenticationType"
      :error="violations?.authenticationType"
      
      class="capitalize"
    >
      <UInput
        id="server_authenticationType"
        v-model="item.authenticationType"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="integrations"
      name="integrations"
      :error="violations?.integrations"
      
      class="capitalize"
    >
      <FormRepeater
        :values="item.integrations"
        @update="(values: any[]) => (item.integrations = values)"
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
import type { Server } from "~/types/server";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: Server;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Server>({ ...props.values });

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
  (e: "submit", item: Server): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
