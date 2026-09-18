<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails de l'intégration">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #left>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour aux intégrations"
          to="/integrations"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto space-y-6">
        <IntegrationShow
          :key="refreshKey"
          @edit="onEdit"
          @back="navigateTo('/integrations')"
        />
      </div>
    </div>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'intégration">
      <template #body>
        <IntegrationUpdate
          v-if="selectedIntegration && selectedIntegrationId"
          :id="selectedIntegrationId"
          :item="selectedIntegration"
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
import IntegrationShow from "~/components/integration/IntegrationShow.vue";
import IntegrationUpdate from "~/components/integration/IntegrationUpdate.vue";
import { getIdFromIri } from "~/utils/resource";
import type { Integration } from "~/types/integration";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails Intégration - Project Manager",
});

const route = useRoute();
const refreshKey = ref(0);
const isEditModalOpen = ref(false);
const selectedIntegration = ref<Integration | null>(null);

const selectedIntegrationId = computed(() => {
  if (selectedIntegration.value?.["@id"]) {
    return getIdFromIri(selectedIntegration.value["@id"]);
  }
  return (route.params.id as string) || "";
});

function onEdit(item?: Integration) {
  if (item) {
    selectedIntegration.value = item;
    isEditModalOpen.value = true;
  }
}

function onUpdated() {
  isEditModalOpen.value = false;
  refreshKey.value++;
}

function onDeleted() {
  isEditModalOpen.value = false;
  navigateTo("/integrations");
}
</script>
