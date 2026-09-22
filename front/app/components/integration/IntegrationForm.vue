<template>
  <form class="space-y-5" @submit.prevent="emitSubmit">
    <UFormField
      label="Nom de l'intégration"
      name="name"
      :error="violations?.name"
      required
    >
      <UInput
        id="integration_name"
        v-model="item.name"
        class="w-full"
        type="text"
        placeholder="Ex: Jenkins Production, Forge Gitea, Mantis, SonarQube, Nexus"
        required
      />
    </UFormField>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <UFormField
        label="Type d'outil"
        name="type"
        :error="violations?.type"
        required
      >
        <USelect
          v-model="item.type"
          :items="typeOptions"
          value-key="value"
          label-key="label"
          class="w-full"
          placeholder="Sélectionner un type"
        />
      </UFormField>

      <UFormField
        label="Serveur associé"
        name="server"
        :error="violations?.server"
        required
      >
        <USelect
          v-model="item.server"
          :items="serverOptions"
          value-key="value"
          label-key="label"
          class="w-full"
          placeholder="Sélectionner un serveur"
        />
      </UFormField>
    </div>

    <!-- Récapitulatif du serveur sélectionné -->
    <div
      v-if="selectedServerInfo"
      class="rounded-lg border border-neutral-200 dark:border-neutral-800 p-4 bg-neutral-50 dark:bg-neutral-900/40 space-y-2 text-xs"
    >
      <div class="flex items-center gap-1.5 font-semibold text-neutral-900 dark:text-neutral-100">
        <UIcon name="i-heroicons-server" class="size-4 text-primary" />
        <span>Détails du serveur : {{ selectedServerInfo.name }}</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-neutral-600 dark:text-neutral-400">
        <div>
          <span class="font-medium text-neutral-500">Hôte :</span>
          {{ selectedServerInfo.host }}:{{ selectedServerInfo.port }}
        </div>
        <div>
          <span class="font-medium text-neutral-500">Utilisateur :</span>
          {{ selectedServerInfo.username || 'Non renseigné' }}
        </div>
        <div>
          <span class="font-medium text-neutral-500">Authentification :</span>
          {{ selectedServerInfo.authenticationType ? (typeof selectedServerInfo.authenticationType === 'object' ? selectedServerInfo.authenticationType.name : selectedServerInfo.authenticationType) : 'Standard' }}
        </div>
      </div>
    </div>

    <!-- Surcharge du proxy pour cette intégration -->
    <UFormField
      label="Surcharge du proxy réseau (optionnel)"
      name="proxy"
      :error="violations?.proxy"
      description="Par défaut, les requêtes héritent du proxy configuré sur le serveur associé."
    >
      <USelect
        v-model="selectedProxyIri"
        :items="proxyOptions"
        value-key="value"
        label-key="label"
        class="w-full"
        placeholder="Choisir un proxy pour cette intégration..."
      />
    </UFormField>

    <!-- Activation -->
    <div class="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-800 p-3">
      <div>
        <label for="integration_enabled" class="text-sm font-medium text-neutral-900 dark:text-neutral-100 cursor-pointer">
          Intégration active
        </label>
        <p class="text-xs text-neutral-500">Permet à l'application de dialoguer avec ce service.</p>
      </div>
      <input
        id="integration_enabled"
        v-model="item.enabled"
        type="checkbox"
        class="h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary cursor-pointer"
      />
    </div>

    <!-- Diagnostic et test de connexion -->
    <div class="rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700 p-4 space-y-3 bg-neutral-50/50 dark:bg-neutral-900/20">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Test de connectivité</p>
          <p class="text-xs text-neutral-500">Vérifier immédiatement les paramètres d'accès auprès du service via le serveur associé.</p>
        </div>
        <UButton
          type="button"
          color="neutral"
          variant="outline"
          icon="i-heroicons-bolt"
          :loading="isTesting"
          :disabled="!item.server"
          label="Tester la connexion"
          @click="onTestConnection"
        />
      </div>

      <UAlert
        v-if="testResult"
        :color="testResult.success ? 'success' : 'error'"
        :icon="testResult.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
        variant="subtle"
        :title="testResult.statusMessage"
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
import { ref, toRef, watch, computed } from "vue";
import type { Integration, ConnectionTestResult } from "~/types/integration";
import type { Server } from "~/types/server";
import type { SubmissionErrors } from "~/types/error";
import { ENTRYPOINT } from "~/utils/config";
import { getIdFromIri } from "~/utils/resource";
import { useIntegrationTestStore } from "~/stores/integration/test";

const props = defineProps<{
  values?: Integration;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");
const testStore = useIntegrationTestStore();

const typeOptions = [
  { label: "Jenkins CI", value: "jenkins" },
  { label: "Gitea", value: "gitea" },
  { label: "Mantis Bug Tracker", value: "mantis" },
  { label: "SonarQube", value: "sonarqube" },
  { label: "Nexus Repository", value: "nexus" },
];

const item = ref<any>({
  name: props.values?.name || "",
  type: props.values?.type || "jenkins",
  enabled: props.values?.enabled ?? true,
  server: props.values?.server
    ? (typeof props.values.server === "object" ? props.values.server?.["@id"] : props.values.server)
    : null,
  status: props.values?.status || "unknown",
});

// Charger la liste des serveurs et proxies disponibles
const serversData = ref<any>(null);
const proxiesData = ref<any[]>([]);
const selectedProxyIri = ref<string>(
  props.values?.proxy
    ? typeof props.values.proxy === "object"
      ? props.values.proxy["@id"]
      : props.values.proxy
    : ""
);

try {
  const token = useCookie<string | null>("jwt_token").value;
  const headers: Record<string, string> = {
    Accept: "application/ld+json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
  const [srvRes, prxRes] = await Promise.all([
    $fetch<any>(`${ENTRYPOINT}/servers`, { headers }).catch(() => null),
    $fetch<any>(`${ENTRYPOINT}/proxies`, { headers }).catch(() => null),
  ]);
  serversData.value = srvRes;
  proxiesData.value = prxRes?.member || prxRes?.["hydra:member"] || [];
} catch {
  // Non-bloquant pour le formulaire
}

const serverMembers = computed<Server[]>(() => {
  return serversData.value?.member || serversData.value?.["hydra:member"] || [];
});

const serverOptions = computed(() => {
  return serverMembers.value.map((s: any) => ({
    label: `${s.name} (${s.host}:${s.port})`,
    value: s["@id"],
  }));
});

const proxyOptions = computed(() => {
  return [
    { label: "Hériter du serveur associé (Recommandé)", value: "" },
    ...proxiesData.value
      .filter((p: any) => p.enabled !== false)
      .map((p: any) => ({
        label: `${p.name} (${p.url})`,
        value: p["@id"],
      })),
  ];
});

const selectedServerInfo = computed(() => {
  if (!item.value.server) return null;
  return serverMembers.value.find((s: any) => s["@id"] === item.value.server) || null;
});

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        server: typeof newVal.server === "object" ? newVal.server?.["@id"] : (newVal.server || null),
      };
      selectedProxyIri.value = newVal.proxy
        ? typeof newVal.proxy === "object"
          ? newVal.proxy["@id"]
          : newVal.proxy
        : "";
    }
  },
  { immediate: true, deep: true }
);

const isTesting = ref(false);
const testResult = ref<ConnectionTestResult | null>(null);

async function onTestConnection() {
  if (!item.value.server) {
    testResult.value = {
      success: false,
      status: "error",
      statusMessage: "Veuillez sélectionner un serveur avant de tester la connexion.",
    };
    return;
  }

  isTesting.value = true;
  testResult.value = null;

  try {
    const existingId = props.values?.["@id"] ? getIdFromIri(props.values["@id"]) : null;

    if (existingId) {
      // Tester l'intégration existante
      testResult.value = await testStore.testExisting(existingId);
    } else {
      // Tester à la volée avant création
      testResult.value = await testStore.testTransient({
        type: item.value.type,
        server: item.value.server,
        proxy: selectedProxyIri.value || null,
      });
    }
  } catch (err: any) {
    testResult.value = {
      success: false,
      status: "error",
      statusMessage: err.message || "Échec du test de connexion.",
    };
  } finally {
    isTesting.value = false;
  }
}

const emit = defineEmits<{
  (e: "submit", item: Integration): void;
}>();

function emitSubmit() {
  const payload: any = {
    name: item.value.name,
    type: item.value.type,
    enabled: Boolean(item.value.enabled),
    server: item.value.server,
    proxy: selectedProxyIri.value ? selectedProxyIri.value : null,
  };

  emit("submit", payload);
}
</script>
