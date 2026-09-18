<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails {{titleUcFirst}}">
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
          to="/{{lc}}s"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto">
        <{{titleUcFirst}}Show @back="navigateTo('/{{lc}}s')" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {{titleUcFirst}}Show from "~/components/{{lc}}/{{titleUcFirst}}Show.vue";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails {{titleUcFirst}} - Project Manager",
});
</script>
