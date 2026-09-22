<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Détails du Proxy
        </h3>
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
        <dt class="text-sm font-medium text-neutral-500">ID / IRI</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          {{ item['@id'] || item.id }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-semibold">
          {{ item.name }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">URL du proxy</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          {{ item.url }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom d'utilisateur</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.username || '— (non authentifié)' }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Exclusions (noProxy)</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <span v-if="item.noProxy" class="font-mono text-xs bg-neutral-100 dark:bg-neutral-800 px-2 py-1 rounded">
            {{ item.noProxy }}
          </span>
          <span v-else class="text-neutral-400">—</span>
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Statut</dt>
        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
          <UBadge :color="item.enabled ? 'success' : 'neutral'" variant="subtle" size="sm">
            {{ item.enabled ? 'Actif' : 'Inactif' }}
          </UBadge>
        </dd>
      </div>
      <div v-if="item.createdAt" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Créé le</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ formatDateTime(item.createdAt) }}
        </dd>
      </div>
      <div v-if="item.updatedAt" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Mis à jour le</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ formatDateTime(item.updatedAt) }}
        </dd>
      </div>
    </div>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFetchItem } from "~/composables/api";
import { formatDateTime } from "~/utils/date";
import type { Proxy } from "~/types/proxy";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: Proxy;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: Proxy): void;
}>();

const route = useRoute();
const router = useRouter();

const currentId = computed(() => {
  return props.id || (route.params.id ? decodeURIComponent(route.params.id as string) : undefined);
});

const item = ref<Proxy | undefined>(props.item);
const isLoading = ref(false);
const error = ref<string | undefined>(undefined);

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
    const data = await useFetchItem<Proxy>(`proxies/${idToLoad}`);
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
  router.push({ path: "/proxies" });
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
