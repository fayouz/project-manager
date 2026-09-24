<template>
  <UCard :ui="{ body: 'p-4' }" class="h-full">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-start gap-3 min-w-0">
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
          :class="statusBgClass"
        >
          <UIcon :name="integrationIcon" class="w-5 h-5" />
        </div>
        <div class="min-w-0">
          <NuxtLink
            :to="detailPath"
            class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate hover:text-primary-600 dark:hover:text-primary-400 block"
          >
            {{ integration.name || integration.type || "Intégration" }}
          </NuxtLink>
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 truncate">
            {{ integration.type || "Type inconnu" }}
          </p>
          <div class="flex items-center gap-2 mt-2 flex-wrap">
            <UBadge :color="statusColor" variant="subtle" size="xs">
              {{ statusLabel }}
            </UBadge>
            <span
              v-if="latencyMs !== null"
              class="text-[11px] font-mono text-neutral-500 dark:text-neutral-400"
            >
              {{ latencyMs }} ms
            </span>
          </div>
        </div>
      </div>

      <UButton
        size="xs"
        color="neutral"
        variant="soft"
        icon="i-heroicons-bolt"
        :loading="isTesting"
        label="Vérifier"
        @click="runTest"
      />
    </div>

    <div class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-800 space-y-1.5">
      <div class="flex items-center justify-between gap-2 text-xs">
        <span class="text-neutral-500 dark:text-neutral-400">Dernière vérification</span>
        <span class="text-neutral-700 dark:text-neutral-300 truncate">
          {{ lastCheckedLabel }}
        </span>
      </div>
      <p
        v-if="statusMessage"
        class="text-[11px] text-neutral-500 dark:text-neutral-400 line-clamp-2"
      >
        {{ statusMessage }}
      </p>
    </div>
  </UCard>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Integration } from "~/types/integration";
import { getIdFromIri } from "~/utils/resource";
import { useIntegrationTestStore } from "~/stores/integration/test";

const props = withDefaults(
  defineProps<{
    integration: Integration;
    autoCheck?: boolean;
  }>(),
  {
    autoCheck: false,
  }
);

const emit = defineEmits<{
  updated: [integration: Integration];
}>();

const testStore = useIntegrationTestStore();
const isTesting = ref(false);
const latencyMs = ref<number | null>(null);
const localStatus = ref(props.integration.status);
const localStatusMessage = ref(props.integration.statusMessage);
const localLastCheckedAt = ref(props.integration.lastCheckedAt);

watch(
  () => props.integration,
  (value) => {
    localStatus.value = value.status;
    localStatusMessage.value = value.statusMessage;
    localLastCheckedAt.value = value.lastCheckedAt;
  },
  { deep: true }
);

const integrationId = computed(
  () => getIdFromIri(props.integration["@id"]) || props.integration.id
);

const detailPath = computed(() => {
  const id = integrationId.value;
  return id ? `/integrations/${id}` : "/integrations";
});

const integrationIcon = computed(() => {
  switch (props.integration.type?.toLowerCase()) {
    case "gitea":
      return "i-heroicons-code-bracket";
    case "jenkins":
      return "i-heroicons-arrow-path-rounded-square";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    default:
      return "i-heroicons-puzzle-piece";
  }
});

const statusColor = computed<"success" | "error" | "neutral" | "warning">(() => {
  if (localStatus.value === "healthy") return "success";
  if (localStatus.value === "error") return "error";
  if (localStatus.value === "unknown") return "warning";
  return "neutral";
});

const statusLabel = computed(() => {
  switch (localStatus.value) {
    case "healthy":
      return "Opérationnelle";
    case "error":
      return "Erreur";
    case "unknown":
      return "Inconnu";
    default:
      return localStatus.value || "Non testé";
  }
});

const statusBgClass = computed(() => {
  switch (statusColor.value) {
    case "success":
      return "bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400";
    case "error":
      return "bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400";
    case "warning":
      return "bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400";
    default:
      return "bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400";
  }
});

const statusMessage = computed(() => localStatusMessage.value || "");

const lastCheckedLabel = computed(() => {
  if (!localLastCheckedAt.value) return "Jamais testé";
  try {
    return new Date(localLastCheckedAt.value).toLocaleString("fr-FR", {
      dateStyle: "short",
      timeStyle: "short",
    });
  } catch {
    return localLastCheckedAt.value;
  }
});

async function runTest() {
  const id = integrationId.value;
  if (!id || isTesting.value) return;

  isTesting.value = true;
  const startedAt = Date.now();

  try {
    const res = await testStore.testExisting(id);
    latencyMs.value = Date.now() - startedAt;

    localStatus.value = res.status;
    localStatusMessage.value = res.statusMessage;
    localLastCheckedAt.value = res.lastCheckedAt || new Date().toISOString();

    emit("updated", {
      ...props.integration,
      status: localStatus.value,
      statusMessage: localStatusMessage.value,
      lastCheckedAt: localLastCheckedAt.value,
    });
  } catch (err: any) {
    latencyMs.value = Date.now() - startedAt;
    localStatus.value = "error";
    localStatusMessage.value =
      err?.data?.statusMessage || err?.message || "Échec du test";
    localLastCheckedAt.value = new Date().toISOString();
  } finally {
    isTesting.value = false;
  }
}

if (props.autoCheck) {
  // Fire-and-forget on mount when requested
  void runTest();
}
</script>
