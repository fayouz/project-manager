<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails de l'organisation">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #left>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour aux organisations"
          to="/organisations"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto space-y-6">
        <OrganisationShow
          :key="refreshKey"
          @edit="onEdit"
          @back="navigateTo('/organisations')"
        />
      </div>
    </div>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'organisation">
      <template #body>
        <OrganisationUpdate
          v-if="selectedOrg && selectedOrgId"
          :id="selectedOrgId"
          :item="selectedOrg"
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
import OrganisationShow from "~/components/organisation/OrganisationShow.vue";
import OrganisationUpdate from "~/components/organisation/OrganisationUpdate.vue";
import { getIdFromIri } from "~/utils/resource";
import type { Organisation } from "~/types/organisation";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails Organisation - Project Manager",
});

const route = useRoute();
const refreshKey = ref(0);
const isEditModalOpen = ref(false);
const selectedOrg = ref<Organisation | null>(null);

const selectedOrgId = computed(() => {
  if (selectedOrg.value?.["@id"]) {
    return getIdFromIri(selectedOrg.value["@id"]);
  }
  return (route.params.id as string) || "";
});

function onEdit(item?: Organisation) {
  if (item) {
    selectedOrg.value = item;
    isEditModalOpen.value = true;
  }
}

function onUpdated() {
  isEditModalOpen.value = false;
  refreshKey.value++;
}

function onDeleted() {
  isEditModalOpen.value = false;
  navigateTo("/organisations");
}
</script>
