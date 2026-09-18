<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Projects">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Projects</span>
          <UBadge color="primary" variant="subtle" size="xs">
            {{ filteredProjects.length }} projet{{ filteredProjects.length > 1 ? 's' : '' }}
          </UBadge>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          icon="i-heroicons-plus"
          label="Nouveau projet"
          @click="openCreateModal"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
      <!-- En-tête & Barre d'outils -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Liste des projets</h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">Gérez l'ensemble des projets de votre organisation.</p>
        </div>

        <div class="flex items-center gap-3">
          <UInput
            v-model="searchQuery"
            placeholder="Rechercher un projet..."
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

      <!-- Alerte d'erreur -->
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-heroicons-exclamation-triangle"
        :title="typeof error === 'string' ? error : error?.message || 'Erreur lors du chargement des projets'"
      />

      <!-- État de chargement -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-3">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-primary" />
        <span class="text-sm text-neutral-500">Chargement des projets...</span>
      </div>

      <!-- Aucun projet trouvé -->
      <div v-else-if="filteredProjects.length === 0" class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
        <UIcon name="i-heroicons-folder-open" class="mx-auto size-12 text-neutral-400" />
        <h3 class="mt-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ searchQuery ? 'Aucun résultat pour cette recherche' : 'Aucun projet pour le moment' }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ searchQuery ? 'Essayez de modifier vos critères de recherche.' : 'Créez votre premier projet pour commencer à collaborer.' }}
        </p>
        <div class="mt-4">
          <UButton
            v-if="!searchQuery"
            color="primary"
            icon="i-heroicons-plus"
            label="Nouveau projet"
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
          v-for="project in filteredProjects"
          :key="project['@id'] || project.id"
          class="hover:shadow-md transition-shadow group border border-neutral-200 dark:border-neutral-800"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 flex items-center justify-center shrink-0">
                <UIcon name="i-heroicons-folder" class="w-5 h-5" />
              </div>
              <UBadge
                color="primary"
                variant="subtle"
                size="xs"
              >
                ID: {{ getIdFromIri(project['@id']) || project.id }}
              </UBadge>
            </div>

            <div>
              <h3 class="font-semibold text-base text-neutral-900 dark:text-neutral-100 hover:text-primary cursor-pointer" @click="goToShow(project)">
                {{ project.name || 'Projet sans nom' }}
              </h3>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                Organisation: {{ formatOrganisation(project.organisation) }}
              </p>

              <!-- Intégrations associées au projet -->
              <div v-if="getProjectIntegrations(project).length > 0" class="flex flex-wrap gap-1.5 mt-2.5">
                <UBadge
                  v-for="pi in getProjectIntegrations(project)"
                  :key="pi['@id'] || pi.id"
                  :color="getTypeBadgeColor(getIntegrationType(pi))"
                  variant="subtle"
                  size="xs"
                  class="flex items-center gap-1 text-[11px]"
                  :title="getIntegrationSummary(pi)"
                >
                  <UIcon :name="getTypeIcon(getIntegrationType(pi))" class="size-3" />
                  <span>{{ getIntegrationShortLabel(pi) }}</span>
                </UBadge>
              </div>
            </div>
          </div>

          <template #footer>
            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 pt-1">
              <span class="flex items-center gap-1 font-mono">
                {{ project['@id'] }}
              </span>
              <div class="flex items-center gap-1">
                <UButton
                  :to="`/projects/${getIdFromIri(project['@id']) || project.id}`"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-eye"
                  aria-label="Voir le projet"
                  title="Voir les détails"
                />
                <UButton
                  variant="ghost"
                  color="primary"
                  size="xs"
                  icon="i-heroicons-pencil-square"
                  aria-label="Modifier le projet"
                  @click="openEditModal(project)"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  size="xs"
                  icon="i-heroicons-trash"
                  aria-label="Supprimer le projet"
                  @click="handleDelete(project)"
                />
              </div>
            </div>
          </template>
        </UCard>
      </div>

      <!-- Vue Tableau (Composant ProjectList généré) -->
      <div v-else class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-800 p-4">
        <ProjectList
          @create="openCreateModal"
          @edit="openEditModal"
          @show="goToShow"
          @deleted="onDeleted"
        />
      </div>
    </div>

    <!-- Modale de Création -->
    <UModal v-model:open="isCreateModalOpen" title="Nouveau projet">
      <template #body>
        <ProjectCreate
          :show-back="false"
          @created="onCreated"
          @cancel="isCreateModalOpen = false"
        />
      </template>
    </UModal>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le projet">
      <template #body>
        <ProjectUpdate
          v-if="selectedProject"
          :id="selectedProjectId"
          :item="selectedProject"
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
import { ref, computed, onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useProjectListStore } from "~/stores/project/list";
import { useProjectDeleteStore } from "~/stores/project/delete";
import { useFetchList, useDeleteItem } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import { getEntrypoint } from "~/utils/config";
import ProjectCreate from "~/components/project/ProjectCreate.vue";
import ProjectUpdate from "~/components/project/ProjectUpdate.vue";
import ProjectList from "~/components/project/ProjectList.vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectintegration";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Projects - Project Manager",
});

const projectListStore = useProjectListStore();
const projectDeleteStore = useProjectDeleteStore();

const { items, isLoading, error } = storeToRefs(projectListStore);
const { deleted: deletedItem } = storeToRefs(projectDeleteStore);

// Données initiales SSR / Client
async function loadProjects() {
  const data = await useFetchList<Project>("projects");
  projectListStore.setData(data);
}

await loadProjects();

// Synchronisation Mercure en temps réel
useMercureList({
  store: projectListStore,
  deleteStore: projectDeleteStore,
});

// Cache réactif des intégrations liées par projet
const allProjectIntegrations = ref<ProjectIntegration[]>([]);

async function loadProjectIntegrationsList() {
  try {
    const entrypoint = getEntrypoint();
    const res: any = await $fetch(`${entrypoint}/project_integrations`, {
      headers: { Accept: "application/ld+json" },
    });
    allProjectIntegrations.value = res?.member || res?.["hydra:member"] || [];
  } catch {
    // Non-bloquant pour la page principale
  }
}

onMounted(() => {
  loadProjectIntegrationsList();
});

function getProjectIntegrations(project: Project): ProjectIntegration[] {
  const projectIri = project["@id"] || `/api/projects/${project.id}`;
  return allProjectIntegrations.value.filter((pi) => {
    const piProj = typeof pi.project === "object" ? pi.project?.["@id"] : pi.project;
    return piProj === projectIri;
  });
}

function getIntegrationType(pi: ProjectIntegration): string {
  if (typeof pi.integration === "object" && pi.integration?.type) {
    return pi.integration.type.toLowerCase();
  }
  return "unknown";
}

function getTypeIcon(type?: string): string {
  switch (type?.toLowerCase()) {
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
}

function getTypeBadgeColor(type?: string): "warning" | "info" | "success" | "primary" | "neutral" {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "warning";
    case "jenkins":
      return "info";
    case "mantis":
      return "success";
    case "sonarqube":
      return "primary";
    default:
      return "neutral";
  }
}

function getIntegrationShortLabel(pi: ProjectIntegration): string {
  const type = getIntegrationType(pi);
  const params = pi.parameters || {};

  if (type === "gitea" && params.repository) {
    return params.repository;
  }
  if (type === "sonarqube" && params.project_key) {
    return params.project_key;
  }
  if (type === "jenkins" && (params.job || params.job_name)) {
    return params.job || params.job_name;
  }
  if (type === "mantis" && params.project_id) {
    return `Mantis: ${params.project_id}`;
  }

  const integ = pi.integration as any;
  return integ?.name || type.toUpperCase();
}

function getIntegrationSummary(pi: ProjectIntegration): string {
  const type = getIntegrationType(pi);
  const integ = pi.integration as any;
  const name = integ?.name || type;
  const params = pi.parameters || {};

  if (type === "gitea" && params.repository) {
    return `${name} - Dépôt : ${params.repository}`;
  }
  if (type === "sonarqube" && params.project_key) {
    return `${name} - Clé : ${params.project_key}`;
  }
  return `${name}`;
}

// État local de la vue
const viewMode = ref<"grid" | "table">("grid");
const searchQuery = ref("");

// Modales
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedProject = ref<Project | null>(null);

const selectedProjectId = computed(() => {
  if (!selectedProject.value) return undefined;
  return (
    getIdFromIri(selectedProject.value["@id"]) ||
    String(selectedProject.value.id || "")
  );
});

// Navigation vers la page Show
function goToShow(project: Project) {
  const id = getIdFromIri(project["@id"]) || project.id;
  if (id) {
    navigateTo(`/projects/${id}`);
  }
}

// Filtrage réactif par recherche
const filteredProjects = computed(() => {
  const list = items.value || [];
  if (!searchQuery.value.trim()) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter((p: Project) => p.name?.toLowerCase().includes(q));
});

function formatOrganisation(org: any) {
  if (!org) return "Aucune";
  if (typeof org === "string") return org;
  return org.name || org["@id"] || "Organisation";
}

// Actions modales
function openCreateModal() {
  isCreateModalOpen.value = true;
}

function openEditModal(project: Project) {
  selectedProject.value = project;
  isEditModalOpen.value = true;
}

// Événements CRUD
async function onCreated(createdProject: Project) {
  isCreateModalOpen.value = false;
  await loadProjects();
  loadProjectIntegrationsList();
}

function onUpdated(updatedProject: Project) {
  isEditModalOpen.value = false;
  projectListStore.updateItem(updatedProject);
}

function onDeleted(deletedProject: Project) {
  isEditModalOpen.value = false;
  projectListStore.deleteItem(deletedProject);
}

async function handleDelete(project: Project) {
  if (
    confirm(
      `Êtes-vous sûr de vouloir supprimer le projet "${project.name || project["@id"]}" ?`
    )
  ) {
    const { error: delError } = await useDeleteItem(project);
    if (!delError.value) {
      projectListStore.deleteItem(project);
      projectDeleteStore.setDeleted(project);
    }
  }
}
</script>
