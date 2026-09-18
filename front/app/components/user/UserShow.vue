<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Détails User
        </h3>
        <div class="flex items-center gap-2">
          <UButton
            v-if="item?.isLdap"
            variant="soft"
            color="warning"
            icon="i-heroicons-arrow-path"
            size="sm"
            label="Rafraîchir LDAP"
            :loading="isRefreshingLdap"
            @click="handleRefreshLdap"
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
      v-if="refreshFeedback"
      :color="refreshFeedback.type === 'success' ? 'success' : 'error'"
      variant="subtle"
      :icon="refreshFeedback.type === 'success' ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
      :title="refreshFeedback.message"
      class="mb-4"
    />

    <div v-if="item" class="divide-y divide-neutral-200 dark:divide-neutral-800">
      <div v-if="userAvatarSrc" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4 items-center">
        <dt class="text-sm font-medium text-neutral-500">Avatar / Photo</dt>
        <dd class="mt-1 sm:col-span-2 sm:mt-0">
          <UAvatar
            :src="userAvatarSrc"
            :alt="item.username || item.email"
            size="lg"
          />
        </dd>
      </div>
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
      <div v-if="item.displayName" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Nom d'affichage</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-medium">
          {{ item.displayName }}
        </dd>
      </div>
      <div v-if="item.firstName || item.lastName" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Prénom & Nom</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ [item.firstName, item.lastName].filter(Boolean).join(' ') }}
        </dd>
      </div>
      <div v-if="item.title" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Titre / Fonction</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.title }}
        </dd>
      </div>
      <div v-if="item.department" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Département / Service</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ item.department }}
        </dd>
      </div>
      <div v-if="item.manager" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Manager</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 flex items-center gap-2">
          <UAvatar
            :src="getManagerAvatar(item.manager)"
            :text="getManagerInitials(item.manager)"
            size="xs"
          />
          <NuxtLink
            v-if="typeof item.manager === 'object' && (item.manager['@id'] || item.manager.id)"
            :to="`/users/${getIdFromIri(item.manager['@id']) || item.manager.id}`"
            class="font-medium text-primary hover:underline flex items-center gap-1.5"
          >
            {{ getManagerName(item.manager) }}
            <span v-if="item.manager.title" class="text-xs text-neutral-500 font-normal">· {{ item.manager.title }}</span>
          </NuxtLink>
          <span v-else class="font-medium">
            {{ getManagerName(item.manager) }}
          </span>
        </dd>
      </div>
      <div v-if="item.managerDn" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Manager (DN)</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono text-xs break-all">
          {{ item.managerDn }}
        </dd>
      </div>
      <div v-if="item.distinguishedName" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">DN LDAP</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0 font-mono text-xs break-all">
          {{ item.distinguishedName }}
        </dd>
      </div>
      <div v-if="item.syncedAt" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt class="text-sm font-medium text-neutral-500">Dernière synchronisation</dt>
        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100 sm:col-span-2 sm:mt-0">
          {{ formatDateTime(item.syncedAt) }}
        </dd>
      </div>
    </div>
  </UCard>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFetchItem } from "~/composables/api";
import { useAuthStore } from "~/stores/auth";
import { getEntrypoint } from "~/utils/config";
import { getIdFromIri } from "~/utils/resource";
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
  (e: "refreshed", item: User): void;
}>();

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

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
const isRefreshingLdap = ref(false);
const refreshFeedback = ref<{ type: "success" | "error"; message: string } | null>(null);

const userAvatarSrc = computed(() => {
  if (item.value?.avatar) return item.value.avatar;
  if (item.value?.image) {
    return item.value.image.startsWith("data:")
      ? item.value.image
      : `data:image/jpeg;base64,${item.value.image}`;
  }
  return undefined;
});

function getManagerAvatar(mgr: any): string | undefined {
  if (!mgr || typeof mgr !== "object") return undefined;
  if (mgr.avatar) return mgr.avatar;
  if (mgr.image) {
    return mgr.image.startsWith("data:")
      ? mgr.image
      : `data:image/jpeg;base64,${mgr.image}`;
  }
  return undefined;
}

function getManagerInitials(mgr: any): string {
  if (!mgr || typeof mgr !== "object") return "M";
  const name = mgr.displayName || mgr.username || mgr.email || "M";
  return name.slice(0, 2).toUpperCase();
}

function getManagerName(mgr: any): string {
  if (!mgr) return "";
  if (typeof mgr !== "object") return String(mgr);
  return mgr.displayName || mgr.username || mgr.email || "Manager";
}

async function handleRefreshLdap() {
  const userId =
    item.value?.id ||
    (item.value?.["@id"] ? getIdFromIri(item.value["@id"]) : currentId.value);
  if (!userId || isRefreshingLdap.value) return;

  isRefreshingLdap.value = true;
  refreshFeedback.value = null;

  try {
    const res = await $fetch<{
      success: boolean;
      message: string;
      user?: User;
    }>(`${getEntrypoint()}/ldap/users/${userId}/refresh`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    });

    if (res.user) {
      item.value = { ...item.value, ...res.user };
      emit("refreshed", item.value);
      if (authStore.user?.id === Number(userId)) {
        await authStore.fetchCurrentUser();
      }
    }

    refreshFeedback.value = {
      type: "success",
      message: res.message || "Informations rafraîchies depuis le LDAP avec succès.",
    };
  } catch (err: any) {
    refreshFeedback.value = {
      type: "error",
      message:
        err?.data?.message || err?.message || "Erreur lors du rafraîchissement LDAP.",
    };
  } finally {
    isRefreshingLdap.value = false;
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
