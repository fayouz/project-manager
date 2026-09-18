<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Navbar du Dashboard -->
    <UDashboardNavbar title="Utilisateurs">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Utilisateurs</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredUsers.length }} compte{{ filteredUsers.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <div class="flex items-center gap-2">
          <UButton
            size="sm"
            color="warning"
            variant="outline"
            icon="i-heroicons-arrow-path"
            label="Synchroniser LDAP"
            :loading="isSyncingLdap"
            @click="handleSyncLdap"
          />
          <UButton
            size="sm"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouvel utilisateur"
            @click="openCreateModal"
          />
        </div>
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Comptes d'accès</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Gestion des utilisateurs, des attributions de rôles et des synchronisations d'annuaires.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher par email ou nom..."
            icon="i-heroicons-magnifying-glass"
            size="sm"
            class="w-64"
          />

          <!-- Sélecteur de vue Grille / Tableau -->
          <div class="flex items-center border border-neutral-200 dark:border-neutral-800 rounded-lg p-0.5 bg-neutral-100 dark:bg-neutral-800">
            <UButton
              size="xs"
              :variant="viewMode === 'grid' ? 'solid' : 'ghost'"
              :color="viewMode === 'grid' ? 'primary' : 'neutral'"
              icon="i-heroicons-squares-2x2"
              aria-label="Vue grille"
              @click="viewMode = 'grid'"
            />
            <UButton
              size="xs"
              :variant="viewMode === 'table' ? 'solid' : 'ghost'"
              :color="viewMode === 'table' ? 'primary' : 'neutral'"
              icon="i-heroicons-bars-3"
              aria-label="Vue tableau"
              @click="viewMode = 'table'"
            />
          </div>
        </div>
      </div>

      <!-- Alerte de synchronisation LDAP -->
      <UAlert
        v-if="syncFeedback"
        :color="syncFeedback.type === 'success' ? 'success' : 'error'"
        variant="subtle"
        :icon="syncFeedback.type === 'success' ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
        :title="syncFeedback.message"
        close
        @close="syncFeedback = null"
      />

      <!-- Alerte d'erreur -->
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des utilisateurs'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des utilisateurs...</span>
      </div>

      <!-- Aucun utilisateur trouvé -->
      <div v-else-if="filteredUsers.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-users" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun utilisateur répertorié' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ searchQuery ? 'Modifiez votre requête de recherche.' : 'Créez votre premier utilisateur pour lui donner accès.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouvel utilisateur"
            @click="openCreateModal"
          />
          <UButton
            v-else
            variant="ghost"
            color="neutral"
            label="Effacer la recherche"
            @click="searchQuery = ''"
          />
        </div>
      </div>

      <!-- Vue Grille (Cartes personnalisées) -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <UCard
          v-for="user in filteredUsers"
          :key="user['@id'] || user.id"
          class="flex flex-col justify-between hover:shadow-md transition-shadow"
        >
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-3 min-w-0">
                <UAvatar
                  :src="getUserAvatar(user)"
                  :text="getInitials(user)"
                  :alt="user.username || user.email"
                  size="md"
                />
                <div class="min-w-0">
                  <h3
                    class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer truncate"
                    @click="goToShow(user)"
                  >
                    {{ user.displayName || user.username || user.email }}
                  </h3>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                    {{ user.email }}
                  </p>
                  <p v-if="user.title || user.department" class="text-[11px] text-neutral-400 truncate">
                    {{ [user.title, user.department].filter(Boolean).join(' · ') }}
                  </p>
                </div>
              </div>
              <UBadge
                color="primary"
                variant="subtle"
                size="xs"
              >
                ID: {{ getIdFromIri(user['@id']) || user.id }}
              </UBadge>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <UBadge
                v-for="role in (Array.isArray(user.roles) ? user.roles : [user.roles])"
                :key="String(role)"
                :color="role === 'ROLE_ADMIN' ? 'primary' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ role }}
              </UBadge>
              <UBadge
                :color="user.isLdap ? 'warning' : 'neutral'"
                variant="soft"
                size="xs"
              >
                {{ user.isLdap ? 'LDAP' : 'Local' }}
              </UBadge>
            </div>

            <div v-if="user.manager" class="text-xs text-neutral-600 dark:text-neutral-400 flex items-center gap-1.5 pt-1 border-t border-neutral-100 dark:border-neutral-800">
              <span class="text-neutral-400 font-medium text-[11px]">Manager :</span>
              <span class="font-medium text-neutral-800 dark:text-neutral-200 truncate">
                {{ typeof user.manager === 'object' ? (user.manager.displayName || user.manager.username || user.manager.email) : user.manager }}
              </span>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="flex items-center gap-1 font-mono text-[11px]">
                {{ user['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  v-if="user.isLdap"
                  variant="ghost"
                  color="warning"
                  size="xs"
                  icon="i-heroicons-arrow-path"
                  aria-label="Rafraîchir depuis LDAP"
                  title="Rafraîchir depuis LDAP (image, mail...)"
                  :loading="refreshingUserId === (getIdFromIri(user['@id']) || user.id)"
                  @click="handleRefreshLdapUser(user)"
                />
                <UButton
                  :to="`/users/${getIdFromIri(user['@id']) || user.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir l'utilisateur"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier l'utilisateur"
                  @click="openEditModal(user)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer l'utilisateur"
                  @click="handleDelete(user)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau (Composant UserList généré) -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <UserList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
          @refreshed="onUserRefreshed"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouvel utilisateur">
      <template #body>
        <UserCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'utilisateur">
      <template #body>
        <UserUpdate
          v-if="selectedUser"
          :id="selectedUserId"
          :item="selectedUser"
          :show-back="false"
          @updated="onUpdated"
          @deleted="onDeleted"
          @cancel="isEditModalOpen = false"
        />
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { storeToRefs } from "pinia";
import type { User } from "~/types/user";
import { useUserListStore } from "~/stores/user/list";
import { useUserDeleteStore } from "~/stores/user/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { useMercureList } from "~/composables/mercureList";
import { getIdFromIri } from "~/utils/resource";
import { getEntrypoint } from "~/utils/config";
import { useAuthStore } from "~/stores/auth";

import UserList from "~/components/user/UserList.vue";
import UserCreate from "~/components/user/UserCreate.vue";
import UserUpdate from "~/components/user/UserUpdate.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Utilisateurs - Project Manager",
});

// Stores
const userListStore = useUserListStore();
const userDeleteStore = useUserDeleteStore();
const authStore = useAuthStore();
const { items, isLoading, error } = storeToRefs(userListStore);

// Synchronisation LDAP rapide
const isSyncingLdap = ref(false);
const syncFeedback = ref<{ message: string; type: "success" | "error" } | null>(null);

async function handleSyncLdap() {
  isSyncingLdap.value = true;
  syncFeedback.value = null;
  try {
    const response = await $fetch<{
      success: boolean;
      created: number;
      updated: number;
      skipped: number;
      total: number;
      message?: string;
    }>(`${getEntrypoint()}/ldap/sync`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        "Content-Type": "application/json",
      },
    });

    if (response.success) {
      syncFeedback.value = {
        type: "success",
        message: `Synchronisation LDAP réussie : ${response.created} créés, ${response.updated} mis à jour, ${response.skipped} ignorés.`,
      };
      await loadUsers();
    } else {
      syncFeedback.value = {
        type: "error",
        message: response.message || "Erreur lors de la synchronisation LDAP.",
      };
    }
  } catch (err: any) {
    syncFeedback.value = {
      type: "error",
      message: err?.data?.message || err?.message || "Erreur lors de la synchronisation LDAP.",
    };
  } finally {
    isSyncingLdap.value = false;
  }
}

// Synchronisation Mercure temps réel
useMercureList({
  store: userListStore,
  deleteStore: userDeleteStore,
});

// Chargement initial des données depuis l'API
async function loadUsers() {
  const data = await useFetchList<User>("users");
  userListStore.setData(data);
}
await loadUsers();

// État local de la vue
const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

// Modales
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedUser = ref<User | null>(null);

const selectedUserId = computed(() => {
  if (!selectedUser.value) return undefined;
  return (
    getIdFromIri(selectedUser.value["@id"]) ||
    String(selectedUser.value.id || "")
  );
});

// Navigation vers la vue Show
function goToShow(user: User) {
  const id = getIdFromIri(user["@id"]) || user.id;
  if (id) {
    navigateTo(`/users/${id}`);
  }
}

// Filtrage réactif par recherche
const filteredUsers = computed(() => {
  const list = items.value || [];
  if (!searchQuery.value.trim()) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(
    (u: User) =>
      u.email?.toLowerCase().includes(q) ||
      u.username?.toLowerCase().includes(q)
  );
});

function getInitials(user: User): string {
  if (user.username) {
    return user.username.slice(0, 2).toUpperCase();
  }
  if (user.email) {
    return user.email.slice(0, 2).toUpperCase();
  }
  return "U";
}

function getUserAvatar(user: User): string | undefined {
  if (user.avatar) return user.avatar;
  if (user.image) {
    return user.image.startsWith("data:")
      ? user.image
      : `data:image/jpeg;base64,${user.image}`;
  }
  return undefined;
}

const refreshingUserId = ref<string | number | undefined>(undefined);

async function handleRefreshLdapUser(user: User) {
  const id = getIdFromIri(user["@id"]) || user.id;
  if (!id || refreshingUserId.value) return;

  refreshingUserId.value = id;
  syncFeedback.value = null;

  try {
    const res = await $fetch<{
      success: boolean;
      message: string;
      user?: User;
    }>(`${getEntrypoint()}/ldap/users/${id}/refresh`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    });

    if (res.user) {
      userListStore.updateItem(res.user);
      onUserRefreshed(res.user);
      syncFeedback.value = {
        type: "success",
        message:
          res.message ||
          `Utilisateur "${user.username || user.email}" rafraîchi avec succès depuis LDAP.`,
      };
    }
  } catch (err: any) {
    syncFeedback.value = {
      type: "error",
      message:
        err?.data?.message ||
        err?.message ||
        "Erreur lors du rafraîchissement LDAP de l'utilisateur.",
    };
  } finally {
    refreshingUserId.value = undefined;
  }
}

function onUserRefreshed(refreshedUser: User) {
  userListStore.updateItem(refreshedUser);
  const refreshedId = Number(getIdFromIri(refreshedUser["@id"]) || refreshedUser.id);
  if (authStore.user?.id === refreshedId) {
    authStore.fetchCurrentUser();
  }
}

// Actions modales
function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(user: User) {
  selectedUser.value = user;
  isEditModalOpen.value = true;
}

// Événements CRUD
async function onCreated(createdUser: User) {
  isCreateModalOpen.value = false;
  await loadUsers();
}

function onUpdated(updatedUser: User) {
  isEditModalOpen.value = false;
  userListStore.updateItem(updatedUser);
}

function onDeleted(deletedUser: User) {
  isEditModalOpen.value = false;
  userListStore.deleteItem(deletedUser);
}

async function handleDelete(user: User) {
  if (
    confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${user.username || user.email || user['@id']}" ?`)
  ) {
    const { error: delError } = await useDeleteItem(user);
    if (!delError.value) {
      onDeleted(user);
    }
  }
}
</script>
