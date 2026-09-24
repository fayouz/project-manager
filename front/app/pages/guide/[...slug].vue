<template>
  <UPage v-if="page">
    <template #left>
      <UPageAside>
        <UContentNavigation :navigation="navigation" highlight />
      </UPageAside>
    </template>

    <UPageHeader :title="page.title" :description="page.description" />

    <UPageBody>
      <ContentRenderer :value="page" />

      <USeparator v-if="surround?.length" />

      <UContentSurround :surround="surround" />
    </UPageBody>

    <template v-if="page.body?.toc?.links?.length" #right>
      <UContentToc :links="page.body.toc.links" />
    </template>
  </UPage>
</template>

<script setup lang="ts">
definePageMeta({
  layout: "guide",
});

const navigation = inject<Ref<unknown> | undefined>("navigation");

const route = useRoute();
const path = computed(() => {
  const slug = Array.isArray(route.params.slug) ? route.params.slug.join("/") : "";
  return slug ? `/guide/${slug}` : "/guide";
});

const { data: page } = await useAsyncData(`guide-page-${path.value}`, () =>
  queryCollection("guide").path(path.value).first(),
);

if (!page.value) {
  throw createError({ statusCode: 404, statusMessage: "Page non trouvée", fatal: true });
}

const { data: surround } = await useAsyncData(`guide-surround-${path.value}`, () =>
  queryCollectionItemSurroundings("guide", path.value, {
    fields: ["title", "description"],
  }),
);

useSeoMeta({
  title: page.value?.title,
  description: page.value?.description,
});
</script>
