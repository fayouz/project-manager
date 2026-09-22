<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails du serveur">
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
          to="/servers"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto">
        <ServerShow @back="navigateTo('/servers')" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import ServerShow from "~/components/server/ServerShow.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails du serveur - Project Manager",
});
</script>
