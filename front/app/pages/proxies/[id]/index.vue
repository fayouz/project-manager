<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails du proxy">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #left>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour"
          to="/proxies"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto">
        <ProxyShow :show-back="false" @back="navigateTo('/proxies')" @edit="isEditModalOpen = true" />
      </div>
    </div>

    <!-- Modale d'édition depuis la page de détails -->
    <UModal v-model:open="isEditModalOpen" title="Modifier le proxy">
      <template #body>
        <ProxyUpdate
          :id="routeId"
          :show-back="false"
          @updated="onUpdated"
          @deleted="navigateTo('/proxies')"
          @cancel="isEditModalOpen = false"
        />
      </template>
    </UModal>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import ProxyShow from "~/components/proxy/ProxyShow.vue";
import ProxyUpdate from "~/components/proxy/ProxyUpdate.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails du proxy - Project Manager",
});

const route = useRoute();
const routeId = computed(() => decodeURIComponent(route.params.id as string));
const isEditModalOpen = ref(false);

function onUpdated() {
  isEditModalOpen.value = false;
  // Reload or refresh view
  location.reload();
}
</script>
