<template>
  <UDashboardGroup>
    <UDashboardSidebar id="main-sidebar" collapsible resizable>
      <template #header="{ collapsed, collapse }">
        <div class="flex items-center justify-between w-full overflow-hidden px-1 py-1">
          <NuxtLink to="/dashboard" class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white shrink-0 shadow-sm">
              <UIcon name="i-heroicons-command-line" class="w-5 h-5" />
            </div>
            <div v-if="!collapsed" class="flex flex-col min-w-0">
              <span class="font-bold text-sm truncate text-neutral-900 dark:text-neutral-100 leading-tight">Project Manager</span>
              <span class="text-[11px] text-neutral-500 dark:text-neutral-400 truncate">Workspace</span>
            </div>
          </NuxtLink>

          <UButton
            v-if="!collapsed"
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-chevron-double-left"
            aria-label="Réduire la barre latérale"
            title="Réduire"
            @click="collapse(true)"
          />
        </div>
      </template>

      <template #default="{ collapsed }">
        <div class="flex flex-col gap-4 w-full">
          <UNavigationMenu
            :items="navItems"
            orientation="vertical"
            :collapsed="collapsed"
            class="w-full"
          />
        </div>
      </template>

      <template #footer="{ collapsed, collapse }">
        <!-- Zone en bas pour l'utilisateur connecté réel -->
        <div v-if="collapsed" class="flex flex-col items-center gap-2 py-1 w-full">
          <UTooltip :text="userName">
            <UAvatar
              :src="userAvatar"
              :text="userInitials"
              :alt="userName"
              size="sm"
            />
          </UTooltip>
          <UTooltip v-if="isLdapUser" text="Rafraîchir mes infos LDAP">
            <UButton
              variant="ghost"
              color="warning"
              size="xs"
              icon="i-heroicons-arrow-path"
              aria-label="Rafraîchir mes infos LDAP"
              :loading="isRefreshingSelf"
              @click="handleRefreshSelf"
            />
          </UTooltip>
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-chevron-double-right"
            aria-label="Développer la barre latérale"
            title="Développer"
            @click="collapse(false)"
          />
          <UTooltip text="Déconnexion">
            <UButton
              variant="ghost"
              color="error"
              size="xs"
              icon="i-heroicons-arrow-left-on-rectangle"
              aria-label="Déconnexion"
              @click="handleLogout"
            />
          </UTooltip>
        </div>

        <div v-else class="w-full p-2.5 rounded-xl bg-neutral-100/90 dark:bg-neutral-800/80 border border-neutral-200 dark:border-neutral-700/80 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <UUser
              :name="userName"
              :description="userEmail"
              :avatar="{
                src: userAvatar,
                text: userInitials,
                alt: userName
              }"
              size="sm"
              class="truncate min-w-0"
            />
            <div class="flex items-center gap-1 shrink-0">
              <UButton
                v-if="isLdapUser"
                variant="ghost"
                color="warning"
                size="xs"
                icon="i-heroicons-arrow-path"
                aria-label="Rafraîchir mes données LDAP"
                title="Rafraîchir mes infos LDAP (avatar, email...)"
                :loading="isRefreshingSelf"
                @click="handleRefreshSelf"
              />
              <UBadge
                :color="isLdapUser ? 'warning' : 'primary'"
                variant="subtle"
                size="xs"
                :label="userBadgeLabel"
                class="text-[10px] shrink-0 font-medium"
              />
            </div>
          </div>
          <div class="pt-2 border-t border-neutral-200/80 dark:border-neutral-700/80 flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5 text-[11px] text-neutral-500 dark:text-neutral-400">
              <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
              <span class="truncate">Connecté</span>
            </div>
            <UButton
              variant="ghost"
              color="error"
              size="xs"
              icon="i-heroicons-arrow-left-on-rectangle"
              label="Déconnexion"
              @click="handleLogout"
            />
          </div>
        </div>
      </template>
    </UDashboardSidebar>

    <UDashboardPanel class="flex-1 overflow-hidden">
      <slot />
    </UDashboardPanel>
  </UDashboardGroup>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useAuthStore } from "~/stores/auth";
import { getEntrypoint } from "~/utils/config";

const authStore = useAuthStore();
const isRefreshingSelf = ref(false);

const userName = computed(() => {
  return authStore.user?.username || authStore.user?.email || "Utilisateur";
});

const userEmail = computed(() => {
  return authStore.user?.email || "";
});

const userInitials = computed(() => {
  const name = userName.value;
  return name.slice(0, 2).toUpperCase();
});

const userAvatar = computed(() => {
  if (authStore.user?.avatar) {
    return authStore.user.avatar;
  }
  if (authStore.user?.image) {
    return authStore.user.image.startsWith("data:")
      ? authStore.user.image
      : `data:image/jpeg;base64,${authStore.user.image}`;
  }
  return undefined;
});

const isLdapUser = computed(() => {
  return authStore.user?.isLdap || authStore.user?.type === "ldap";
});

async function handleRefreshSelf() {
  if (!authStore.user?.id || isRefreshingSelf.value) return;

  isRefreshingSelf.value = true;
  try {
    await $fetch(`${getEntrypoint()}/ldap/users/${authStore.user.id}/refresh`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    });
    await authStore.fetchCurrentUser();
  } catch (err: any) {
    console.error("Erreur lors du rafraîchissement LDAP de l'utilisateur:", err);
  } finally {
    isRefreshingSelf.value = false;
  }
}

const userBadgeLabel = computed(() => {
  if (authStore.user?.roles?.includes("ROLE_SUPER_ADMIN")) {
    return "Superadmin";
  }
  if (authStore.user?.roles?.includes("ROLE_ADMIN")) {
    return "Admin";
  }
  return isLdapUser.value ? "LDAP" : "Local";
});

function handleLogout() {
  authStore.logout();
}

const navItems = [
  [
    {
      label: 'Tableau de bord',
      icon: 'i-heroicons-squares-2x2',
      to: '/dashboard'
    },
    {
      label: 'Projects',
      icon: 'i-heroicons-folder',
      to: '/projects'
    },
    {
      label: 'Organisations',
      icon: 'i-heroicons-building-office-2',
      to: '/organisations'
    },
    {
      label: 'Intégrations',
      icon: 'i-heroicons-puzzle-piece',
      to: '/integrations'
    },
    {
      label: 'Utilisateurs',
      icon: 'i-heroicons-users',
      to: '/users'
    }
  ],
  [
    {
      label: 'Paramètres LDAP',
      icon: 'i-heroicons-adjustments-horizontal',
      to: '/settings/ldap'
    },
    {
      label: 'Documentation API',
      icon: 'i-heroicons-arrow-top-right-on-square',
      to: '/docs',
      target: '_blank'
    }
  ]
];
</script>
