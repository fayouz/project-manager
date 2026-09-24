<template>
  <div class="min-h-screen bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <header class="sticky top-0 z-50 backdrop-blur-md bg-white/85 dark:bg-gray-950/85 border-b border-gray-200/80 dark:border-gray-800/80">
      <UContainer>
        <div class="h-16 flex items-center justify-between gap-4">
          <NuxtLink to="/" class="flex items-center gap-2.5 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white shadow-sm">
              <UIcon name="i-heroicons-command-line" class="w-5 h-5" />
            </div>
            <div class="flex flex-col leading-tight">
              <span class="font-bold text-sm">Project Manager</span>
              <span class="text-[10px] uppercase tracking-wider text-primary-600 dark:text-primary-400">Guide</span>
            </div>
          </NuxtLink>

          <UContentSearchButton class="flex-1 max-w-sm" />

          <div class="flex items-center gap-2 shrink-0">
            <UButton
              to="https://github.com/nuxt-ui-templates/docs"
              target="_blank"
              icon="i-simple-icons-github"
              color="neutral"
              variant="ghost"
              size="sm"
            />
            <UColorModeButton />
            <UButton to="/dashboard" color="primary" size="sm" label="Retour à l'application" />
          </div>
        </div>
      </UContainer>
    </header>

    <UMain>
      <UContainer>
        <slot />
      </UContainer>
    </UMain>

    <UContentSearch :navigation="navigation" :files="files" />
  </div>
</template>

<script setup lang="ts">
const { data: navigation } = await useAsyncData("guide-navigation", () =>
  queryCollectionNavigation("guide"),
);

const { data: files } = useLazyAsyncData(
  "guide-search",
  () => queryCollectionSearchSections("guide"),
  { server: false },
);

provide("navigation", navigation);
</script>
