<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <UIcon name="i-heroicons-server" class="size-5 text-primary" />
          <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            Détails du serveur : {{ item?.name || 'Serveur' }}
          </h3>
        </div>
        <div class="flex items-center gap-2">
          <UButton
            v-if="showBack"
            variant="ghost"
            color="neutral"
            icon="i-heroicons-arrow-left"
            size="sm"
            label="Retour"
            @click="goBack"
          />
          <UButton
            variant="soft"
            color="primary"
            icon="i-heroicons-pencil-square"
            size="sm"
            label="Modifier"
            @click="emit('edit', item)"
          />
        </div>
      </div>
    </template>

    <div v-if="isLoading" class="flex justify-center p-6">
      <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-heroicons-exclamation-triangle"
      :title="error"
      class="mb-4"
    />

    <div v-if="item" class="divide-y divide-neutral-200 dark:divide-neutral-800">
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Identifiant IRI</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          {{ item['@id'] }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom du serveur</dt>
        <dd class="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.name }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Hôte & Port</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          {{ item.host }}:{{ item.port }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom d'utilisateur</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.username || 'Non renseigné' }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Type de serveur</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <UBadge color="neutral" variant="subtle" size="sm">
            {{ getServerTypeName(item.type) }}
          </UBadge>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Authentification</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <UBadge
            v-if="getAuthTypeName(item.authenticationType)"
            color="primary"
            variant="subtle"
            size="sm"
          >
            {{ getAuthTypeName(item.authenticationType) }}
          </UBadge>
          <span v-else class="text-neutral-400">Aucune</span>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Proxy réseau</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <div v-if="item.proxy" class="flex items-center gap-2">
            <UBadge color="primary" variant="subtle" size="sm">
              <UIcon name="i-heroicons-globe-alt" class="size-3.5 mr-1 inline" />
              {{ typeof item.proxy === 'object' ? item.proxy.name : item.proxy }}
            </UBadge>
            <span v-if="typeof item.proxy === 'object' && item.proxy.url" class="text-xs font-mono text-neutral-500">
              ({{ item.proxy.url }})
            </span>
          </div>
          <UBadge v-else-if="item.options?.proxy === 'direct'" color="neutral" variant="subtle" size="sm">
            Connexion directe (sans proxy)
          </UBadge>
          <span v-else class="text-neutral-500 text-sm">
            Proxy par défaut du système
          </span>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Options configurées</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <div v-if="item.options && Object.keys(item.options).length > 0" class="space-y-2">
            <div class="flex flex-wrap gap-2">
              <UBadge
                v-for="(val, key) in item.options"
                :key="key"
                color="neutral"
                variant="outline"
              >
                {{ key }}: {{ val }}
              </UBadge>
            </div>
            <pre class="bg-neutral-100 dark:bg-neutral-950 p-2.5 rounded text-xs font-mono overflow-x-auto">{{ JSON.stringify(item.options, null, 2) }}</pre>
          </div>
          <span v-else class="text-neutral-400">Aucune option spécifique</span>
        </dd>
      </div>
    </div>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFetchItem } from "~/composables/api";
import { resolveApiUrl } from "~/utils/config";
import type { Server } from "~/types/server";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: Server;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: Server): void;
}>();

const route = useRoute();
const router = useRouter();

const currentId = computed(() => {
  return props.id || (route.params.id ? decodeURIComponent(route.params.id as string) : undefined);
});

const item = ref<Server | undefined>(props.item);
const isLoading = ref(false);
const error = ref<string | undefined>(undefined);

// Dictionnaires pour la résolution des libellés de types
const serverTypesMap = ref<Record<string, string>>({});
const authTypesMap = ref<Record<string, string>>({});

try {
  const token = useCookie<string | null>("jwt_token").value;
  const headers: Record<string, string> = {
    Accept: "application/ld+json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const [typesRes, authRes] = await Promise.all([
    $fetch<any>(resolveApiUrl("/server_types"), { headers }).catch(() => null),
    $fetch<any>(resolveApiUrl("/server_authentication_types"), { headers }).catch(() => null),
  ]);

  if (typesRes) {
    const members = typesRes.member || typesRes["hydra:member"] || [];
    members.forEach((m: any) => {
      serverTypesMap.value[m["@id"]] = m.name;
    });
  }
  if (authRes) {
    const members = authRes.member || authRes["hydra:member"] || [];
    members.forEach((m: any) => {
      authTypesMap.value[m["@id"]] = m.name;
    });
  }
} catch {
  // Non-bloquant
}

function getServerTypeName(type: any): string {
  if (!type) return "Inconnu";
  if (typeof type === "object" && type.name) return type.name;
  if (typeof type === "string") {
    return serverTypesMap.value[type] || type.split("/").pop() || type;
  }
  return String(type);
}

function getAuthTypeName(authType: any): string {
  if (!authType) return "";
  if (typeof authType === "object" && authType.name) return authType.name;
  if (typeof authType === "string") {
    return authTypesMap.value[authType] || authType.split("/").pop() || authType;
  }
  return String(authType);
}

async function load() {
  if (props.item) {
    item.value = props.item;
    return;
  }
  const idToLoad = currentId.value;
  if (!idToLoad) return;
  isLoading.value = true;
  error.value = undefined;
  try {
    const data = await useFetchItem<Server>(`servers/${idToLoad}`);
    item.value = data.retrieved.value;
    if (data.error.value) {
      error.value = data.error.value?.message || String(data.error.value);
    }
  } catch (err: any) {
    error.value = err.message || "Erreur de chargement";
  } finally {
    isLoading.value = false;
  }
}

function goBack() {
  emit("back");
  router.push({ path: "/servers" });
}

await load();
watch(() => currentId.value, () => load());
watch(
  () => props.item,
  (val) => {
    if (val) item.value = val;
  }
);
</script>
