<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    {{#forEach formFields}}
    <UFormField
      label="{{name}}"
      name="{{name}}"
      :error="violations?.{{name}}"
      {{#if required}}required{{/if}}
      class="capitalize"
    >
      {{#if isRelations}}
      <FormRepeater
        :values="item.{{name}}"
        @update="(values: any[]) => (item.{{name}} = values)"
      />
      {{else}}
      <UInput
        id="{{../lc}}_{{name}}"
        v-model="item.{{name}}"
        class="w-full"
        {{#compare type "==" "dateTime"}}
        type="date"
        {{/compare}}
        {{#compare type "!=" "dateTime"}}
        type="{{type}}"
        {{/compare}}
        {{#if step}}
        step="{{step}}"
        {{/if}}
        placeholder="{{description}}"
      />
      {{/if}}
    </UFormField>
    {{/forEach}}

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
{{#if hasIsRelations}}
import FormRepeater from "~/components/common/FormRepeater.vue";
{{/if}}
import type { {{titleUcFirst}} } from "~/types/{{lc}}";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: {{titleUcFirst}};
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<{{titleUcFirst}}>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        {{#each fields}}
        {{#compare type "==" "dateTime"}}
        {{name}}: formatDateInput(newVal.{{name}}),
        {{/compare}}
        {{#if isEmbeddeds}}
        {{name}}: newVal.{{name}}?.map((sub: any) => sub["@id"] ?? "") ?? [],
        {{else if embedded}}
        {{name}}: newVal.{{name}}?.["@id"],
        {{/if}}
        {{/each}}
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: {{titleUcFirst}}): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
