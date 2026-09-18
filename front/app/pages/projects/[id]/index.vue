<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails du projet">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #left>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour aux projets"
          to="/projects"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-5xl mx-auto space-y-6">
        <ProjectShow
          :key="refreshKey"
          @edit="onEdit"
          @back="navigateTo('/projects')"
        />
      </div>
    </div>

    <!-- Modale de Modification du Projet -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le projet" :ui="{ content: 'sm:max-w-xl' }">
      <template #body>
        <ProjectUpdate
          v-if="selectedProject && selectedProjectId"
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
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import ProjectShow from "~/components/project/ProjectShow.vue";
import ProjectUpdate from "~/components/project/ProjectUpdate.vue";
import { getIdFromIri } from "~/utils/resource";
import type { Project } from "~/types/project";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails du projet - Project Manager",
});

const route = useRoute();
const refreshKey = ref(0);
const isEditModalOpen = ref(false);
const selectedProject = ref<Project | null>(null);

const selectedProjectId = computed(() => {
  if (selectedProject.value?.["@id"]) {
    return getIdFromIri(selectedProject.value["@id"]);
  }
  return (route.params.id as string) || "";
});

function onEdit(item?: Project) {
  if (item) {
    selectedProject.value = item;
    isEditModalOpen.value = true;
  }
}

function onUpdated() {
  isEditModalOpen.value = false;
  refreshKey.value++;
}

function onDeleted() {
  isEditModalOpen.value = false;
  navigateTo("/projects");
}
</script>
