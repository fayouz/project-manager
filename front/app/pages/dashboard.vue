<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Navbar du Dashboard -->
    <UDashboardNavbar title="Tableau de bord">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Tableau de bord</span>
          <UBadge color="primary" variant="subtle" size="xs">Aperçu</UBadge>
        </div>
      </template>

      <template #right>
        <div class="flex items-center gap-2">
          <UButton
            size="sm"
            variant="ghost"
            color="neutral"
            icon="i-heroicons-arrow-path"
            :loading="isRefreshing"
            title="Rafraîchir les données"
            @click="refreshAll"
          />
          <UButton
            to="/projects"
            size="sm"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau projet"
          />
          <UButton
            to="/docs"
            target="_blank"
            variant="ghost"
            color="neutral"
            size="sm"
            icon="i-heroicons-arrow-top-right-on-square"
            title="Swagger API"
          />
        </div>
      </template>
    </UDashboardNavbar>

    <!-- Contenu principal défilable -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- Bannière de bienvenue -->
      <div class="p-6 rounded-2xl bg-gradient-to-r from-primary-600/10 via-primary-500/5 to-transparent border border-primary-500/20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
              {{ greeting }}, {{ authStore.user?.displayName || authStore.user?.username || 'bienvenue' }}
            </h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
              Pilotez vos projets, gérez vos équipes et suivez la santé de vos services en temps réel.
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <UBadge :color="globalHealth.color" variant="soft" size="md">
              <span class="flex items-center gap-1.5">
                <span
                  class="w-2 h-2 rounded-full"
                  :class="globalHealth.dotClass"
                ></span>
                {{ globalHealth.label }}
              </span>
            </UBadge>
          </div>
        </div>
      </div>

      <!-- Alerte d'erreur globale -->
      <UAlert
        v-if="loadError"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        title="Certaines données n'ont pas pu être chargées"
        :description="loadError"
      />

      <!-- Statistiques rapides (KPIs) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <UCard :ui="{ body: 'p-4 sm:p-5' }" class="hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/projects')">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Projets</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                <USkeleton v-if="isLoading.projects" class="h-7 w-10" />
                <template v-else>{{ projects.length }}</template>
              </p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-building-office-2" class="w-3.5 h-3.5" />
                {{ organisations.length }} organisation{{ organisations.length > 1 ? 's' : '' }}
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                <UIcon name="i-heroicons-folder" class="w-6 h-6" />
              </div>
              <DashboardMiniSparkline
                :values="projectsSparkline"
                class="text-primary-500 dark:text-primary-400"
                aria-label="Tendance projets 30 jours"
              />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }" class="hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/users')">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Utilisateurs</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                <USkeleton v-if="isLoading.users" class="h-7 w-10" />
                <template v-else>{{ users.length }}</template>
              </p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-shield-check" class="w-3.5 h-3.5" />
                {{ adminCount }} admin{{ adminCount > 1 ? 's' : '' }}, {{ users.length - adminCount }} membre{{ (users.length - adminCount) > 1 ? 's' : '' }}
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <UIcon name="i-heroicons-users" class="w-6 h-6" />
              </div>
              <DashboardMiniSparkline
                :values="usersSparkline"
                class="text-blue-500 dark:text-blue-400"
                aria-label="Tendance utilisateurs 30 jours"
              />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }" class="hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/servers')">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Serveurs</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                <USkeleton v-if="isLoading.servers" class="h-7 w-10" />
                <template v-else>{{ servers.length }}</template>
              </p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-rocket-launch" class="w-3.5 h-3.5" />
                {{ deploymentServers.length }} serveur{{ deploymentServers.length > 1 ? 's' : '' }} de déploiement
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <UIcon name="i-heroicons-server" class="w-6 h-6" />
              </div>
              <DashboardMiniSparkline
                :values="serversSparkline"
                class="text-emerald-500 dark:text-emerald-400"
                aria-label="Tendance serveurs 30 jours"
              />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }" class="hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/integrations')">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Intégrations</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
                <USkeleton v-if="isLoading.integrations" class="h-7 w-10" />
                <template v-else>{{ integrations.length }}</template>
              </p>
              <p
                class="text-xs mt-1 flex items-center gap-1"
                :class="unhealthyIntegrationsCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'"
              >
                <UIcon :name="unhealthyIntegrationsCount > 0 ? 'i-heroicons-exclamation-triangle' : 'i-heroicons-check-circle'" class="w-3.5 h-3.5" />
                {{ unhealthyIntegrationsCount > 0 ? `${unhealthyIntegrationsCount} en erreur` : 'Toutes opérationnelles' }}
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <UIcon name="i-heroicons-puzzle-piece" class="w-6 h-6" />
              </div>
              <DashboardMiniSparkline
                :values="integrationsSparkline"
                class="text-purple-500 dark:text-purple-400"
                aria-label="Tendance intégrations 30 jours"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- Grille des sections principales -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Projets récents -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-folder" class="w-5 h-5 text-primary-600" />
                <h2 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Projets récents</h2>
              </div>
              <UButton
                to="/projects"
                variant="ghost"
                color="neutral"
                size="xs"
                trailing-icon="i-heroicons-chevron-right"
                label="Voir tous"
              />
            </div>
          </template>

          <div v-if="isLoading.projects" class="space-y-3">
            <USkeleton v-for="n in 3" :key="n" class="h-12 w-full" />
          </div>

          <div v-else-if="recentProjects.length === 0" class="text-center py-8">
            <UIcon name="i-heroicons-folder-open" class="mx-auto size-8 text-neutral-400" />
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aucun projet pour le moment.</p>
            <UButton to="/projects" size="xs" color="primary" variant="soft" class="mt-3" label="Créer un projet" />
          </div>

          <div v-else class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <NuxtLink
              v-for="project in recentProjects"
              :key="project['@id'] || project.id"
              :to="`/projects/${getIdFromIri(project['@id']) || project.id}`"
              class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 hover:opacity-80 transition-opacity"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center shrink-0">
                  <UIcon name="i-heroicons-folder" class="w-5 h-5 text-neutral-600 dark:text-neutral-300" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                    {{ project.name || 'Projet sans nom' }}
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                    {{ formatOrganisation(project.organisation) }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <UBadge color="primary" variant="subtle" size="xs">
                  ID: {{ getIdFromIri(project['@id']) || project.id }}
                </UBadge>
              </div>
            </NuxtLink>
          </div>
        </UCard>

        <!-- Utilisateurs récents -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-users" class="w-5 h-5 text-blue-600" />
                <h2 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Utilisateurs récents</h2>
              </div>
              <UButton
                to="/users"
                variant="ghost"
                color="neutral"
                size="xs"
                trailing-icon="i-heroicons-chevron-right"
                label="Gérer"
              />
            </div>
          </template>

          <div v-if="isLoading.users" class="space-y-3">
            <USkeleton v-for="n in 3" :key="n" class="h-12 w-full" />
          </div>

          <div v-else-if="recentUsers.length === 0" class="text-center py-8">
            <UIcon name="i-heroicons-users" class="mx-auto size-8 text-neutral-400" />
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aucun utilisateur répertorié.</p>
            <UButton to="/users" size="xs" color="primary" variant="soft" class="mt-3" label="Créer un utilisateur" />
          </div>

          <div v-else class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <NuxtLink
              v-for="user in recentUsers"
              :key="user['@id'] || user.id"
              :to="`/users/${getIdFromIri(user['@id']) || user.id}`"
              class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 hover:opacity-80 transition-opacity"
            >
              <div class="flex items-center gap-3 min-w-0">
                <UAvatar
                  :src="getUserAvatar(user)"
                  :text="getInitials(user)"
                  :alt="user.displayName || user.username || user.email"
                  size="sm"
                />
                <div class="min-w-0">
                  <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                    {{ user.displayName || user.username || user.email }}
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                    {{ user.email }}
                  </p>
                </div>
              </div>

              <UBadge
                :color="isAdmin(user) ? 'primary' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ isAdmin(user) ? 'Administrateur' : 'Membre' }}
              </UBadge>
            </NuxtLink>
          </div>
        </UCard>
      </div>

      <!-- Timeline d'activité récente -->
      <UCard>
        <template #header>
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-clock" class="w-5 h-5 text-neutral-600 dark:text-neutral-400" />
            <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Activité récente</h3>
          </div>
        </template>
        <DashboardActivityTimeline
          :activities="activityLog.activities.value"
          :is-loading="activityLog.isLoading.value"
          :error="activityLog.error.value"
        />
      </UCard>

      <!-- État des serveurs -->
      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-server" class="w-5 h-5 text-emerald-600" />
              <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">État des serveurs</h3>
            </div>
            <UButton
              to="/servers"
              variant="ghost"
              color="neutral"
              size="xs"
              trailing-icon="i-heroicons-chevron-right"
              label="Voir tous"
            />
          </div>
        </template>

        <div v-if="isLoading.servers" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <USkeleton v-for="n in 3" :key="n" class="h-28 w-full" />
        </div>

        <div v-else-if="servers.length === 0" class="text-center py-8">
          <UIcon name="i-heroicons-server" class="mx-auto size-8 text-neutral-400" />
          <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aucun serveur configuré.</p>
          <UButton to="/servers" size="xs" color="primary" variant="soft" class="mt-3" label="Ajouter un serveur" />
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <DashboardServerStatusCard
            v-for="server in displayedServers"
            :key="server['@id'] || server.id"
            :server="server"
            :integrations-count="getServerIntegrationsCount(server)"
          />
        </div>
      </UCard>

      <!-- État des intégrations (cartes live) -->
      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-puzzle-piece" class="w-5 h-5 text-purple-600" />
              <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">État des intégrations</h3>
            </div>
            <UButton
              to="/integrations"
              variant="ghost"
              color="neutral"
              size="xs"
              trailing-icon="i-heroicons-chevron-right"
              label="Voir toutes"
            />
          </div>
        </template>

        <div v-if="isLoading.integrations" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <USkeleton v-for="n in 3" :key="n" class="h-28 w-full" />
        </div>

        <div v-else-if="integrations.length === 0" class="text-center py-8">
          <UIcon name="i-heroicons-puzzle-piece" class="mx-auto size-8 text-neutral-400" />
          <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aucune intégration configurée.</p>
          <UButton to="/integrations" size="xs" color="primary" variant="soft" class="mt-3" label="Ajouter une intégration" />
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <DashboardIntegrationLiveCard
            v-for="integration in displayedIntegrations"
            :key="integration['@id'] || integration.id"
            :integration="integration"
            @updated="onIntegrationUpdated"
          />
        </div>
      </UCard>

      <!-- Accès rapides & Liens utiles -->
      <UCard>
        <template #header>
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-command-line" class="w-5 h-5 text-neutral-600 dark:text-neutral-400" />
            <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Services & Raccourcis</h3>
          </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <NuxtLink
            to="/docs"
            target="_blank"
            class="flex items-start gap-3 p-3.5 rounded-xl border border-neutral-200 dark:border-neutral-800 hover:border-primary-500/50 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-all"
          >
            <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 shrink-0">
              <UIcon name="i-heroicons-code-bracket" class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 flex items-center gap-1">
                API Platform Docs
                <UIcon name="i-heroicons-arrow-top-right-on-square" class="w-3.5 h-3.5 text-neutral-400" />
              </p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Documentation Swagger / OpenAPI</p>
            </div>
          </NuxtLink>

          <NuxtLink
            to="/admin"
            target="_blank"
            class="flex items-start gap-3 p-3.5 rounded-xl border border-neutral-200 dark:border-neutral-800 hover:border-primary-500/50 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-all"
          >
            <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 shrink-0">
              <UIcon name="i-heroicons-adjustments-horizontal" class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 flex items-center gap-1">
                Interface Admin
                <UIcon name="i-heroicons-arrow-top-right-on-square" class="w-3.5 h-3.5 text-neutral-400" />
              </p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Administration API Platform</p>
            </div>
          </NuxtLink>

          <NuxtLink
            to="/setup"
            class="flex items-start gap-3 p-3.5 rounded-xl border border-neutral-200 dark:border-neutral-800 hover:border-primary-500/50 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-all"
          >
            <div class="p-2 rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 shrink-0">
              <UIcon name="i-heroicons-cog-6-tooth" class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Configuration</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Paramètres d'authentification et LDAP</p>
            </div>
          </NuxtLink>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useFetchList } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { useAuthStore } from "~/stores/auth";
import type { Project } from "~/types/project";
import type { User } from "~/types/user";
import type { Organisation } from "~/types/organisation";
import type { Server } from "~/types/server";
import type { DeploymentServer } from "~/types/deploymentserver";
import type { Integration } from "~/types/integration";
import { useActivityLog } from "~/composables/useActivityLog";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Tableau de bord - Project Manager",
});

const authStore = useAuthStore();
const activityLog = useActivityLog();

// Données réelles issues de l'API
const projects = ref<Project[]>([]);
const users = ref<User[]>([]);
const organisations = ref<Organisation[]>([]);
const servers = ref<Server[]>([]);
const deploymentServers = ref<DeploymentServer[]>([]);
const integrations = ref<Integration[]>([]);

const isLoading = reactive({
  projects: true,
  users: true,
  organisations: true,
  servers: true,
  deploymentServers: true,
  integrations: true,
});

const loadError = ref<string | undefined>(undefined);
const isRefreshing = ref(false);

async function loadAll() {
  loadError.value = undefined;
  const errors: string[] = [];

  const results = await Promise.allSettled([
    useFetchList<Project>("projects"),
    useFetchList<User>("users"),
    useFetchList<Organisation>("organisations"),
    useFetchList<Server>("servers"),
    useFetchList<DeploymentServer>("deployment_servers"),
    useFetchList<Integration>("integrations"),
  ]);

  const [projectsRes, usersRes, organisationsRes, serversRes, deploymentServersRes, integrationsRes] = results;

  if (projectsRes.status === "fulfilled") {
    projects.value = projectsRes.value.items.value || [];
  } else {
    errors.push("projets");
  }
  isLoading.projects = false;

  if (usersRes.status === "fulfilled") {
    users.value = usersRes.value.items.value || [];
  } else {
    errors.push("utilisateurs");
  }
  isLoading.users = false;

  if (organisationsRes.status === "fulfilled") {
    organisations.value = organisationsRes.value.items.value || [];
  } else {
    errors.push("organisations");
  }
  isLoading.organisations = false;

  if (serversRes.status === "fulfilled") {
    servers.value = serversRes.value.items.value || [];
  } else {
    errors.push("serveurs");
  }
  isLoading.servers = false;

  if (deploymentServersRes.status === "fulfilled") {
    deploymentServers.value = deploymentServersRes.value.items.value || [];
  } else {
    errors.push("serveurs de déploiement");
  }
  isLoading.deploymentServers = false;

  if (integrationsRes.status === "fulfilled") {
    integrations.value = integrationsRes.value.items.value || [];
  } else {
    errors.push("intégrations");
  }
  isLoading.integrations = false;

  if (errors.length > 0) {
    loadError.value = `Impossible de charger : ${errors.join(", ")}.`;
  }
}

async function refreshAll() {
  isRefreshing.value = true;
  try {
    await loadAll();
  } finally {
    isRefreshing.value = false;
  }
}

await loadAll();
activityLog.fetchRecent();

// Salutation dynamique selon l'heure
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 6) return "Bonne nuit";
  if (hour < 18) return "Bonjour";
  return "Bonsoir";
});

// Derniers éléments (les 5 plus récents en fin de liste)
const recentProjects = computed(() => [...projects.value].slice(-5).reverse());
const recentUsers = computed(() => [...users.value].slice(-5).reverse());
const displayedServers = computed(() => servers.value.slice(0, 6));
const displayedIntegrations = computed(() => integrations.value.slice(0, 6));

const adminCount = computed(
  () => users.value.filter((u) => Array.isArray(u.roles) && u.roles.includes("ROLE_ADMIN")).length
);

function isAdmin(user: User): boolean {
  return Array.isArray(user.roles) && user.roles.includes("ROLE_ADMIN");
}

const unhealthyIntegrationsCount = computed(
  () => integrations.value.filter((i) => i.status && i.status !== "healthy").length
);

// Indicateur de santé global du système
const globalHealth = computed(() => {
  if (unhealthyIntegrationsCount.value > 0) {
    return {
      label: `${unhealthyIntegrationsCount.value} intégration${unhealthyIntegrationsCount.value > 1 ? "s" : ""} en erreur`,
      color: "warning" as const,
      dotClass: "bg-amber-500",
    };
  }
  if (loadError.value) {
    return {
      label: "Données partiellement indisponibles",
      color: "error" as const,
      dotClass: "bg-red-500",
    };
  }
  return {
    label: "Système opérationnel",
    color: "success" as const,
    dotClass: "bg-emerald-500",
  };
});

/**
 * Construit une série cumulative sur 30 jours à partir de `createdAt`.
 * Si aucune date n'est disponible, renvoie une courbe plate au total courant.
 */
function buildCumulativeSparkline(
  items: Array<{ createdAt?: string | null }>,
  days = 30
): number[] {
  const total = items.length;
  const series = Array.from({ length: days }, () => 0);

  const now = new Date();
  const start = new Date(now);
  start.setHours(0, 0, 0, 0);
  start.setDate(start.getDate() - (days - 1));

  let datedCount = 0;
  for (const item of items) {
    if (!item.createdAt) continue;
    const created = new Date(item.createdAt);
    if (Number.isNaN(created.getTime())) continue;

    const day = new Date(created);
    day.setHours(0, 0, 0, 0);
    const diff = Math.floor((day.getTime() - start.getTime()) / 86_400_000);
    if (diff >= 0 && diff < days) {
      series[diff] += 1;
      datedCount += 1;
    } else if (diff < 0) {
      // Créé avant la fenêtre : compte comme base initiale
      series[0] += 1;
      datedCount += 1;
    }
  }

  if (datedCount === 0) {
    return Array.from({ length: days }, () => total);
  }

  // Cumul
  let running = 0;
  return series.map((n) => {
    running += n;
    return running;
  });
}

const projectsSparkline = computed(() =>
  buildCumulativeSparkline(projects.value as Array<{ createdAt?: string }>)
);
const usersSparkline = computed(() =>
  buildCumulativeSparkline(users.value as Array<{ createdAt?: string }>)
);
const serversSparkline = computed(() =>
  buildCumulativeSparkline(servers.value as Array<{ createdAt?: string }>)
);
const integrationsSparkline = computed(() =>
  buildCumulativeSparkline(integrations.value)
);

function getServerIntegrationsCount(server: Server): number {
  if (Array.isArray(server.integrations)) {
    return server.integrations.length;
  }

  const serverIri = server["@id"];
  const serverId = getIdFromIri(serverIri) || server.id;
  if (!serverId && !serverIri) return 0;

  return integrations.value.filter((integration) => {
    const linked = integration.server;
    if (!linked) return false;
    if (typeof linked === "string") {
      return linked === serverIri || getIdFromIri(linked) === String(serverId);
    }
    return (
      linked["@id"] === serverIri ||
      getIdFromIri(linked["@id"]) === String(serverId) ||
      linked.id === serverId
    );
  }).length;
}

function onIntegrationUpdated(updated: Integration) {
  const key = updated["@id"] || updated.id;
  const index = integrations.value.findIndex(
    (item) => (item["@id"] || item.id) === key
  );
  if (index >= 0) {
    integrations.value[index] = {
      ...integrations.value[index],
      ...updated,
    };
  }
}

function formatOrganisation(org: any) {
  if (!org) return "Aucune organisation";
  if (typeof org === "string") return org;
  return org.name || org["@id"] || "Organisation";
}

function getInitials(user: User): string {
  if (user.username) return user.username.slice(0, 2).toUpperCase();
  if (user.email) return user.email.slice(0, 2).toUpperCase();
  return "U";
}

function getUserAvatar(user: User): string | undefined {
  if (user.avatar) return user.avatar;
  if (user.image) {
    return user.image.startsWith("data:") ? user.image : `data:image/jpeg;base64,${user.image}`;
  }
  return undefined;
}
</script>
