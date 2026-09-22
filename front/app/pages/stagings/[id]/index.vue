<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails Staging">
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
          to="/stagings"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto">
        <StagingShow @back="navigateTo('/stagings')" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import StagingShow from "~/components/staging/StagingShow.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails Staging - Project Manager",
});
</script>
