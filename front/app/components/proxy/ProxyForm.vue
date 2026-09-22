<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="Nom du proxy"
      name="name"
      :error="violations?.name"
      required
    >
      <UInput
        id="proxy_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder="Ex: Proxy Entreprise"
        required
      />
    </UFormField>

    <UFormField
      label="URL du proxy"
      name="url"
      :error="violations?.url"
      required
    >
      <UInput
        id="proxy_url"
        v-model="item.url"
        class="w-full"
        type="text"
        placeholder="Ex: http://proxy.example.com:8080"
        required
      />
    </UFormField>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <UFormField
        label="Nom d'utilisateur (optionnel)"
        name="username"
        :error="violations?.username"
      >
        <UInput
          id="proxy_username"
          v-model="item.username"
          class="w-full"
          type="text"
          placeholder="Ex: proxy_user"
        />
      </UFormField>

      <UFormField
        label="Mot de passe (optionnel)"
        name="password"
        :error="violations?.password"
      >
        <UInput
          id="proxy_password"
          v-model="item.password"
          class="w-full"
          type="password"
          placeholder="••••••••"
        />
      </UFormField>
    </div>

    <UFormField
      label="Exclusions (noProxy)"
      name="noProxy"
      :error="violations?.noProxy"
      description="Domaines ou hôtes exclus du proxy, séparés par des virgules."
    >
      <UInput
        id="proxy_noProxy"
        v-model="item.noProxy"
        class="w-full"
        type="text"
        placeholder="Ex: localhost, 127.0.0.1, .local, internal.corp"
      />
    </UFormField>

    <div class="pt-1">
      <UCheckbox
        id="proxy_enabled"
        v-model="item.enabled"
        label="Activer ce proxy"
      />
    </div>

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
import type { Proxy } from "~/types/proxy";
import type { SubmissionErrors } from "~/types/error";

const props = defineProps<{
  values?: Proxy;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<Proxy>({
  enabled: true,
  ...props.values,
});

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        enabled: newVal.enabled ?? true,
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: Proxy): void;
}>();

function emitSubmit() {
  const payload: Partial<Proxy> = {
    name: item.value.name,
    url: item.value.url,
    username: item.value.username || undefined,
    noProxy: item.value.noProxy || undefined,
    enabled: item.value.enabled ?? true,
  };

  if (item.value.password) {
    payload.password = item.value.password;
  }

  if (item.value["@id"]) {
    payload["@id"] = item.value["@id"];
  }

  emit("submit", payload as Proxy);
}
</script>
