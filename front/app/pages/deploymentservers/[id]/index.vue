<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails DeploymentServer">
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
          to="/deploymentservers"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto">
        <DeploymentServerShow @back="navigateTo('/deploymentservers')" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import DeploymentServerShow from "~/components/deploymentserver/DeploymentServerShow.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails DeploymentServer - Project Manager",
});
</script>
