<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="Nom de l'organisation"
      name="name"
      :error="violations?.name"
      required
    >
      <UInput
        id="organisation_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder="Ex: Acme Corporation"
        required
      />
    </UFormField>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
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
import type { Organisation } from "~/types/organisation";
import type { SubmissionErrors } from "~/types/error";

const props = defineProps<{
  values?: Organisation;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Organisation>({ ...props.values });

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
  (e: "submit", item: Organisation): void;
}>();

function emitSubmit() {
  const payload: Partial<Organisation> = {
    name: item.value.name,
  };
  if (item.value["@id"]) {
    payload["@id"] = item.value["@id"];
  }
  emit("submit", payload as Organisation);
}
</script>
