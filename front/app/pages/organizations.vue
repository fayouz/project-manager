<script setup lang="ts">
definePageMeta({
  middleware: [
    () => navigateTo('/organisations', { redirectCode: 301 })
  ]
});
</script>

<template>
  <div></div>
</template>
