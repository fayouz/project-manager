<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="entityType"
      name="entityType"
      :error="violations?.entityType"
      
      class="capitalize"
    >
      <UInput
        id="activitylog_entityType"
        v-model="item.entityType"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="entityId"
      name="entityId"
      :error="violations?.entityId"
      
      class="capitalize"
    >
      <UInput
        id="activitylog_entityId"
        v-model="item.entityId"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="entityLabel"
      name="entityLabel"
      :error="violations?.entityLabel"
      
      class="capitalize"
    >
      <UInput
        id="activitylog_entityLabel"
        v-model="item.entityLabel"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="action"
      name="action"
      :error="violations?.action"
      
      class="capitalize"
    >
      <UInput
        id="activitylog_action"
        v-model="item.action"
        class="w-full"
        type="text"
        placeholder=""
      />
    </UFormField>
    <UFormField
      label="actor"
      name="actor"
      :error="violations?.actor"
      
      class="capitalize"
    >
      <UInput
        id="activitylog_actor"
        v-model="item.actor"
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
        id="activitylog_createdAt"
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
import type { ActivityLog } from "~/types/activitylog";
import type { SubmissionErrors } from "~/types/error";
import { formatDateInput } from "~/utils/date";

const props = defineProps<{
  values?: ActivityLog;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<ActivityLog>({ ...props.values });

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        actor: newVal.actor?.["@id"],
                createdAt: formatDateInput(newVal.createdAt),
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: ActivityLog): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
