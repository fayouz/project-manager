<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Détails User
        </h3>
        <div class="flex items-center gap-2">
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

    <div v-if="item" class="divide-y divide-neutral-200 dark:divide-neutral-800">
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Identifiant API</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono">
          {{ item['@id'] }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Adresse email</dt>
        <dd class="mt-1 text-sm font-semibold text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.email }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom d'utilisateur</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.username || 'Non défini' }}
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Rôles attribués</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <div class="flex flex-wrap gap-1.5">
            <UBadge
              v-for="role in (Array.isArray(item.roles) ? item.roles : [item.roles])"
              :key="String(role)"
              :color="role === 'ROLE_ADMIN' ? 'primary' : 'neutral'"
              variant="subtle"
              size="xs"
            >
              {{ role }}
            </UBadge>
          </div>
        </dd>
      </div>
      <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Type de compte</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          <UBadge
            :color="item.isLdap ? 'warning' : 'neutral'"
            variant="soft"
            size="xs"
          >
            {{ item.isLdap ? 'Synchronisé via LDAP' : 'Compte local' }}
          </UBadge>
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
import type { User } from "~/types/user";

const props = withDefaults(
  defineProps<{
    id?: string;
    item?: User;
    showBack?: boolean;
  }>(),
  {
    showBack: true,
  }
);

const emit = defineEmits<{
  (e: "back"): void;
  (e: "edit", item?: User): void;
}>();

const route = useRoute();
const router = useRouter();

const currentId = computed(() => {
  return props.id || (route.params.id ? String(route.params.id) : undefined);
});

function handleBack() {
  emit("back");
  router.push("/users");
}

const item = ref<User | undefined>(props.item);
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
    const data = await useFetchItem<User>(`users/${idToLoad}`);
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

await load();
watch(() => currentId.value, () => load());
watch(
  () => props.item,
  (val) => {
    if (val) item.value = val;
  }
);
</script>
