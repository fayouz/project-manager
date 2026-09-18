<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="Nom du projet"
      name="name"
      :error="violations?.name"
      required
    >
      <UInput
        id="project_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder="Ex: Refonte Dashboard"
        required
      />
    </UFormField>

    <UFormField
      label="Organisation"
      name="organisation"
      :error="violations?.organisation"
      required
    >
      <USelect
        v-if="orgOptions.length > 0"
        v-model="item.organisation"
        :items="orgOptions"
        value-key="value"
        label-key="label"
        class="w-full"
        placeholder="Sélectionner une organisation"
      />
      <UInput
        v-else
        id="project_organisation"
        v-model="item.organisation"
        class="w-full"
        type="text"
        placeholder="/api/organisations/1"
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
import { ref, toRef, watch, computed } from "vue";
import type { Project } from "~/types/project";
import type { SubmissionErrors } from "~/types/error";
import { ENTRYPOINT } from "~/utils/config";

const props = defineProps<{
  values?: Project;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Project>({ ...props.values });

// Charger les organisations disponibles pour la relation
const orgsData = ref<any>(null);
try {
  const token = useCookie<string | null>("jwt_token").value;
  const headers: Record<string, string> = {
    Accept: "application/ld+json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
  orgsData.value = await $fetch<any>(`${ENTRYPOINT}/organisations`, { headers });
} catch {
  // Non-bloquant pour le formulaire
}

const orgOptions = computed(() => {
  const members =
    orgsData.value?.member || orgsData.value?.["hydra:member"] || [];
  return members.map((m: any) => ({
    label: m.name || m["@id"],
    value: m["@id"],
  }));
});

// Pré-remplir l'organisation si non renseignée
if (!item.value.organisation && orgOptions.value.length > 0) {
  item.value.organisation = orgOptions.value[0].value;
}

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        organisation:
          typeof newVal.organisation === "object"
            ? newVal.organisation?.["@id"]
            : newVal.organisation,
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: Project): void;
}>();

function emitSubmit() {
  emit("submit", item.value);
}
</script>
