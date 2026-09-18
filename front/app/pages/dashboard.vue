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
              Bienvenue sur votre espace Project Manager
            </h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
              Pilotez vos projets, gérez vos équipes et suivez la santé de vos services en temps réel.
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <UBadge color="success" variant="soft" size="md">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Système opérationnel
              </span>
            </UBadge>
          </div>
        </div>
      </div>

      <!-- Statistiques rapides (KPIs) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Projets actifs</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">3</p>
              <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-check-circle" class="w-3.5 h-3.5" />
                2 en production
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
              <UIcon name="i-heroicons-folder" class="w-6 h-6" />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Utilisateurs</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">2</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-users" class="w-3.5 h-3.5" />
                1 admin, 1 membre
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <UIcon name="i-heroicons-users" class="w-6 h-6" />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Services API</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">En ligne</p>
              <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-server" class="w-3.5 h-3.5" />
                PHP FrankenPHP & DB
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <UIcon name="i-heroicons-server" class="w-6 h-6" />
            </div>
          </div>
        </UCard>

        <UCard :ui="{ body: 'p-4 sm:p-5' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Sécurité & Auth</p>
              <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">Conforme</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1">
                <UIcon name="i-heroicons-shield-check" class="w-3.5 h-3.5" />
                Session active
              </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <UIcon name="i-heroicons-shield-check" class="w-6 h-6" />
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

          <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <div
              v-for="project in recentProjects"
              :key="project.id"
              class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center shrink-0">
                  <UIcon :name="project.icon" class="w-5 h-5 text-neutral-600 dark:text-neutral-300" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                    {{ project.name }}
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                    {{ project.description }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <UBadge
                  :color="project.statusColor"
                  variant="subtle"
                  size="xs"
                >
                  {{ project.status }}
                </UBadge>
              </div>
            </div>
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

          <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <div
              v-for="user in recentUsers"
              :key="user.id"
              class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <UAvatar
                  :text="user.initials"
                  :alt="user.name"
                  size="sm"
                />
                <div class="min-w-0">
                  <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                    {{ user.name }}
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                    {{ user.email }}
                  </p>
                </div>
              </div>

              <UBadge
                :color="user.roleColor"
                variant="subtle"
                size="xs"
              >
                {{ user.role }}
              </UBadge>
            </div>
          </div>
        </UCard>
      </div>

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

<script setup>
definePageMeta({
  layout: 'dashboard'
});

useHead({
  title: 'Tableau de bord - Project Manager'
});

const recentProjects = [
  {
    id: 1,
    name: 'Plateforme E-Commerce',
    description: 'Boutique en ligne avec gestion de catalogue et paiements',
    status: 'En production',
    statusColor: 'success',
    icon: 'i-heroicons-folder'
  },
  {
    id: 2,
    name: 'Portail Client API',
    description: 'Interface de gestion des clés API et métriques',
    status: 'En développement',
    statusColor: 'primary',
    icon: 'i-heroicons-folder'
  },
  {
    id: 3,
    name: 'Migration Infrastructure',
    description: 'Mise à niveau des clusters Kubernetes et bases de données',
    status: 'Planifié',
    statusColor: 'neutral',
    icon: 'i-heroicons-folder'
  }
];

const recentUsers = [
  {
    id: 1,
    name: 'Admin Principal',
    email: 'admin@project-manager.local',
    initials: 'AD',
    role: 'Administrateur',
    roleColor: 'primary'
  },
  {
    id: 2,
    name: 'Jean Dupont',
    email: 'jean.dupont@example.com',
    initials: 'JD',
    role: 'Développeur',
    roleColor: 'neutral'
  }
];
</script>
