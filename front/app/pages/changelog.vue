<template>
  <UPageSection
    :ui="{
      root: 'py-16 sm:py-24',
      container: 'max-w-3xl'
    }"
  >
    <template #top>
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold tracking-tight">Changelog</h1>
        <p class="mt-3 text-lg text-muted">
          Les dernières nouveautés et améliorations de Project Manager.
        </p>
      </div>
    </template>

    <UChangelogVersions
      as="main"
      :indicator-motion="false"
    >
      <UChangelogVersion
        v-for="version in versions"
        :key="version.path"
        :title="version.title"
        :description="version.description"
        :date="version.date"
        :badge="version.badge"
        :ui="{
          container: 'max-w-2xl min-w-0',
          header: 'border-b border-default pb-4',
          title: 'text-2xl'
        }"
      >
        <template #body>
          <ContentRenderer :value="version" />
        </template>
      </UChangelogVersion>
    </UChangelogVersions>
  </UPageSection>
</template>

<script setup lang="ts">
definePageMeta({
  layout: "guide",
});

const { data: versions } = await useAsyncData("changelog-versions", async () => {
  const docs = await queryCollection("changelog").all();
  return [...docs].sort(
    (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime(),
  );
});

useSeoMeta({
  title: "Changelog",
  description: "Les dernières nouveautés et améliorations de Project Manager.",
});
</script>
