<template>
  <div
    v-if="url"
    class="relative overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900"
    :class="sizeClass"
  >
    <iframe
      :src="url"
      class="absolute inset-0 w-full h-full border-0"
      loading="lazy"
      sandbox="allow-scripts allow-same-origin"
      referrerpolicy="no-referrer"
    />
    <a
      :href="url"
      target="_blank"
      rel="noopener noreferrer"
      class="absolute bottom-1 right-1 z-10"
      @click.stop
    >
      <UButton
        size="xs"
        color="neutral"
        variant="solid"
        icon="i-heroicons-arrow-top-right-on-square"
      />
    </a>
  </div>
  <div
    v-else
    class="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700 text-neutral-400"
    :class="sizeClass"
  >
    <UIcon name="i-heroicons-photo" class="size-6" />
    <span class="text-xs mt-1">Aucun aperçu</span>
  </div>
</template>

<script lang="ts" setup>
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    url?: string;
    size?: "sm" | "md" | "lg";
  }>(),
  {
    size: "md",
  }
);

const sizeClass = computed(() => {
  return {
    sm: "w-24 h-16",
    md: "w-full aspect-video",
    lg: "w-full h-64",
  }[props.size];
});

const url = computed(() => {
  if (!props.url) return undefined;
  return props.url.startsWith("http://") || props.url.startsWith("https://") ? props.url : `https://${props.url}`;
});
</script>
