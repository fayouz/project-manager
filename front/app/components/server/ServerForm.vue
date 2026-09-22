<template>
  <form class="space-y-5" @submit.prevent="emitSubmit">
    <UFormField
      label="Nom du serveur"
      name="name"
      :error="violations?.name"
      required
    >
      <UInput
        id="server_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder="Ex: Jenkins Master, Gitea Prod, SonarQube..."
        required
      />
    </UFormField>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="sm:col-span-2">
        <UFormField
          label="Hôte / Adresse IP"
          name="host"
          :error="violations?.host"
          required
        >
          <UInput
            id="server_host"
            v-model="item.host"
            class="w-full"
            type="text"
            placeholder="Ex: jenkins.bm-energies.com ou 192.168.1.10"
            required
          />
        </UFormField>
      </div>

      <div>
        <UFormField
          label="Port"
          name="port"
          :error="violations?.port"
          required
        >
          <UInput
            id="server_port"
            v-model.number="item.port"
            class="w-full"
            type="number"
            placeholder="8080"
            required
          />
        </UFormField>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <UFormField
        label="Type de serveur"
        name="type"
        :error="violations?.type"
        required
      >
        <USelect
          v-model="selectedTypeIri"
          :items="typeOptions"
          value-key="value"
          label-key="label"
          class="w-full"
          placeholder="Sélectionner un type (HTTP, LDAP...)"
        />
      </UFormField>

      <UFormField
        label="Type d'authentification"
        name="authenticationType"
        :error="violations?.authenticationType"
      >
        <USelect
          v-model="selectedAuthTypeIri"
          :items="authTypeOptions"
          value-key="value"
          label-key="label"
          class="w-full"
          placeholder="Sélectionner une méthode"
        />
      </UFormField>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <UFormField
        label="Nom d'utilisateur / Compte"
        name="username"
        :error="violations?.username"
      >
        <UInput
          id="server_username"
          v-model="item.username"
          class="w-full"
          type="text"
          placeholder="Ex: pinf14 ou admin"
        />
      </UFormField>

      <UFormField
        label="Mot de passe / Jeton secret"
        name="password"
        :error="violations?.password"
      >
        <UInput
          id="server_password"
          v-model="item.password"
          class="w-full"
          type="password"
          :placeholder="props.values ? '•••••••• (laisser vide pour ne pas modifier)' : 'Mot de passe ou API token'"
        />
      </UFormField>
    </div>

    <UFormField
      label="Serveur proxy"
      name="proxy"
      :error="violations?.proxy"
      description="Sélectionnez un proxy réseau pour router les requêtes vers ce serveur."
    >
      <USelect
        v-model="selectedProxyIri"
        :items="proxyOptions"
        value-key="value"
        label-key="label"
        class="w-full"
        placeholder="Sélectionner un proxy"
      />
    </UFormField>

    <UFormField
      label="Options avancées (JSON)"
      name="options"
      :error="violations?.options || optionsError"
      help="Exemple : { &quot;protocol&quot;: &quot;http&quot; }"
    >
      <UTextarea
        id="server_options"
        v-model="optionsJson"
        class="w-full font-mono text-xs"
        :rows="3"
        placeholder='{ "protocol": "http" }'
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
import type { Server } from "~/types/server";
import type { SubmissionErrors } from "~/types/error";
import { resolveApiUrl } from "~/utils/config";

const props = defineProps<{
  values?: Server;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");
const optionsError = ref<string | null>(null);

const item = ref<Server>({
  name: props.values?.name || "",
  host: props.values?.host || "",
  port: props.values?.port ?? 80,
  username: props.values?.username || "",
  password: "",
  options: props.values?.options || {},
});

const selectedTypeIri = ref<string | undefined>(
  props.values?.type
    ? typeof props.values.type === "object"
      ? props.values.type["@id"]
      : props.values.type
    : undefined
);

const selectedAuthTypeIri = ref<string | undefined>(
  props.values?.authenticationType
    ? typeof props.values.authenticationType === "object"
      ? props.values.authenticationType["@id"]
      : props.values.authenticationType
    : undefined
);

const selectedProxyIri = ref<string>(
  props.values?.proxy
    ? typeof props.values.proxy === "object"
      ? props.values.proxy["@id"] || ""
      : props.values.proxy
    : props.values?.options?.proxy === "direct"
      ? "direct"
      : ""
);

const optionsJson = ref<string>(
  props.values?.options && Object.keys(props.values.options).length > 0
    ? JSON.stringify(props.values.options, null, 2)
    : '{\n  "protocol": "http"\n}'
);

// Charger les types de serveurs disponibles
const serverTypesData = ref<any[]>([]);
const serverAuthTypesData = ref<any[]>([]);
const proxiesData = ref<any[]>([]);

try {
  const token = useCookie<string | null>("jwt_token").value;
  const headers: Record<string, string> = {
    Accept: "application/ld+json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const [typesRes, authRes, proxiesRes] = await Promise.all([
    $fetch<any>(resolveApiUrl("/server_types"), { headers }).catch(() => null),
    $fetch<any>(resolveApiUrl("/server_authentication_types"), { headers }).catch(() => null),
    $fetch<any>(resolveApiUrl("/proxies"), { headers }).catch(() => null),
  ]);

  if (typesRes) {
    serverTypesData.value = typesRes.member || typesRes["hydra:member"] || [];
  }
  if (authRes) {
    serverAuthTypesData.value = authRes.member || authRes["hydra:member"] || [];
  }
  if (proxiesRes) {
    proxiesData.value = proxiesRes.member || proxiesRes["hydra:member"] || [];
  }
} catch {
  // Non bloquant
}

const typeOptions = computed(() => {
  return serverTypesData.value.map((t: any) => ({
    label: t.name || t["@id"],
    value: t["@id"],
  }));
});

const authTypeOptions = computed(() => {
  const hasAucune = serverAuthTypesData.value.some((a: any) => a.name === "Aucune");
  const options = [
    ...(!hasAucune ? [{ label: "Aucune / Par défaut", value: "" }] : []),
    ...serverAuthTypesData.value.map((a: any) => ({
      label: a.name || a["@id"],
      value: a["@id"],
    })),
  ];
  return options;
});

const proxyOptions = computed(() => {
  return [
    { label: "Proxy par défaut du système (HTTP_PROXY)", value: "" },
    { label: "Connexion directe (sans proxy)", value: "direct" },
    ...proxiesData.value
      .filter((p: any) => p.enabled !== false)
      .map((p: any) => ({
        label: `${p.name} (${p.url})`,
        value: p["@id"],
      })),
  ];
});

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        name: newVal.name || "",
        host: newVal.host || "",
        port: newVal.port ?? 80,
        username: newVal.username || "",
        password: "",
        options: newVal.options || {},
      };
      selectedTypeIri.value = newVal.type
        ? typeof newVal.type === "object"
          ? newVal.type["@id"]
          : newVal.type
        : undefined;
      selectedAuthTypeIri.value = newVal.authenticationType
        ? typeof newVal.authenticationType === "object"
          ? newVal.authenticationType["@id"]
          : newVal.authenticationType
        : undefined;
      selectedProxyIri.value = newVal.proxy
        ? typeof newVal.proxy === "object"
          ? newVal.proxy["@id"] || ""
          : newVal.proxy
        : newVal.options?.proxy === "direct"
          ? "direct"
          : "";
      optionsJson.value =
        newVal.options && Object.keys(newVal.options).length > 0
          ? JSON.stringify(newVal.options, null, 2)
          : '{\n  "protocol": "http"\n}';
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: Server): void;
}>();

function emitSubmit() {
  optionsError.value = null;
  let parsedOptions: Record<string, any> = {};

  if (optionsJson.value?.trim()) {
    try {
      parsedOptions = JSON.parse(optionsJson.value.trim());
    } catch (e: any) {
      optionsError.value = "Le format JSON des options est invalide.";
      return;
    }
  }

  const payload: any = {
    name: item.value.name,
    host: item.value.host,
    port: item.value.port ? Number(item.value.port) : 80,
    options: parsedOptions,
  };

  if (item.value.username) {
    payload.username = item.value.username;
  } else {
    payload.username = null;
  }

  if (item.value.password) {
    payload.password = item.value.password;
  }

  if (selectedTypeIri.value) {
    payload.type = selectedTypeIri.value;
  }

  if (selectedAuthTypeIri.value) {
    payload.authenticationType = selectedAuthTypeIri.value;
  } else {
    payload.authenticationType = null;
  }

  if (selectedProxyIri.value === "direct") {
    payload.proxy = null;
    parsedOptions.proxy = "direct";
  } else if (selectedProxyIri.value) {
    payload.proxy = selectedProxyIri.value;
    delete parsedOptions.proxy;
  } else {
    payload.proxy = null;
    delete parsedOptions.proxy;
  }

  emit("submit", payload);
}
</script>
