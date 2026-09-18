<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <UIcon
            :name="getIntegrationIcon(item?.type)"
            class="size-6 text-primary"
          />
          <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ item?.name || "Détails Intégration" }}
          </h3>
          <UBadge
            v-if="item?.status"
            :color="statusColor"
            variant="subtle"
            size="sm"
          >
            {{ statusLabel }}
          </UBadge>
        </div>

        <div class="flex items-center gap-2">
          <UButton
            v-if="item"
            variant="outline"
            color="neutral"
            icon="i-heroicons-bolt"
            size="sm"
            :loading="isTesting"
            label="Tester la connexion"
            @click="testCurrentIntegration"
          />
          <UButton
            v-if="showBack"
            variant="ghost"
            color="neutral"
            icon="i-heroicons-arrow-left"
            size="sm"
            label="Retour"
            @click="handleBack"
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

    <UAlert
      v-if="testResult"
      :color="testResult.success ? 'success' : 'error'"
      :icon="testResult.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
      variant="subtle"
      :title="testResult.statusMessage"
      class="mb-4"
    />

    <div v-if="item" class="divide-y divide-neutral-200 dark:divide-neutral-800">
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom</dt>
        <dd class="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.name }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Type d'outil</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 flex items-center gap-2">
          <UIcon
            :name="getIntegrationIcon(item.type)"
            class="size-4 text-primary"
          />
          <span class="capitalize font-medium">{{ item.type }}</span>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">État de santé</dt>
        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0 flex items-center gap-2">
          <UBadge :color="statusColor" variant="subtle" size="sm">
            {{ statusLabel }}
          </UBadge>
          <span v-if="item.statusMessage" class="text-xs text-neutral-500">
            {{ item.statusMessage }}
          </span>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Statut</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <UBadge :color="item.enabled ? 'success' : 'neutral'" variant="soft" size="xs">
            {{ item.enabled ? "Active" : "Désactivée" }}
          </UBadge>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Serveur associé</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          <span>{{ typeof item.server === 'object' ? item.server.name || item.server['@id'] : item.server }}</span>
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Dernière vérification</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.lastCheckedAt ? formatDateTime(item.lastCheckedAt) : "Jamais testé" }}
        </dd>
      </div>

      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Créé le</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ formatDateTime(item.createdAt) }}
        </dd>
      </div>
    </div>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, watch, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFetchItem } from "~/composables/api";
import { formatDateTime } from "~/utils/date";
import { getIdFromIri } from "~/utils/resource";
import type { Integration, ConnectionTestResult } from "~/types/integration";
import { useIntegrationTestStore } from "~/stores/integration/test";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: Integration;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: Integration): void;
}>();

const route = useRoute();
const router = useRouter();

const currentId = computed(() => {
  return props.id || (route.params.id ? String(route.params.id) : undefined);
});

function handleBack() {
  emit("back");
  router.push("/integrations");
}

const item = ref<Integration | undefined>(props.item);
const isLoading = ref(false);
const error = ref<string | undefined>(undefined);
const isTesting = ref(false);
const testResult = ref<ConnectionTestResult | null>(null);

const testStore = useIntegrationTestStore();

const statusColor = computed(() => {
  if (item.value?.status === "healthy") return "success";
  if (item.value?.status === "error") return "error";
  return "neutral";
});

const statusLabel = computed(() => {
  if (item.value?.status === "healthy") return "Opérationnel";
  if (item.value?.status === "error") return "Erreur";
  return "Non testé";
});

function getIntegrationIcon(type?: string): string {
  switch (type?.toLowerCase()) {
    case "jenkins":
      return "i-heroicons-cpu-chip";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    default:
      return "i-heroicons-code-bracket";
  }
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
    const data = await useFetchItem<Integration>(`integrations/${idToLoad}`);
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

async function testCurrentIntegration() {
  const targetId = item.value?.["@id"] ? getIdFromIri(item.value["@id"]) : (currentId.value || props.id);
  if (!targetId) return;

  isTesting.value = true;
  testResult.value = null;

  try {
    const res = await testStore.testExisting(targetId);
    testResult.value = res;
    if (item.value) {
      item.value.status = res.status;
      item.value.statusMessage = res.statusMessage;
      item.value.lastCheckedAt = res.lastCheckedAt || new Date().toISOString();
    }
  } catch (err: any) {
    testResult.value = {
      success: false,
      status: "error",
      statusMessage: err.message || "Erreur lors du test de connexion.",
    };
  } finally {
    isTesting.value = false;
  }
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
