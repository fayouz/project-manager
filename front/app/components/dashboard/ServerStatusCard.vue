<template>
  <UCard
    :ui="{ body: 'p-4' }"
    class="hover:shadow-md transition-shadow cursor-pointer h-full"
    @click="goToServer"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-start gap-3 min-w-0">
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
          :class="statusBgClass"
        >
          <UIcon name="i-heroicons-server" class="w-5 h-5" />
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate">
            {{ server.name || "Serveur sans nom" }}
          </p>
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 truncate font-mono">
            {{ hostLabel }}
          </p>
          <div class="flex items-center gap-2 mt-2 flex-wrap">
            <UBadge color="neutral" variant="subtle" size="xs">
              {{ typeLabel }}
            </UBadge>
            <UBadge :color="statusColor" variant="subtle" size="xs">
              {{ statusLabel }}
            </UBadge>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
      <span class="text-xs text-neutral-500 dark:text-neutral-400 flex items-center gap-1.5">
        <UIcon name="i-heroicons-puzzle-piece" class="w-3.5 h-3.5" />
        Intégrations
      </span>
      <UBadge color="primary" variant="soft" size="xs">
        {{ integrationsCount }}
      </UBadge>
    </div>
  </UCard>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Server } from "~/types/server";
import { getIdFromIri } from "~/utils/resource";

const props = defineProps<{
  server: Server;
  integrationsCount: number;
}>();

const hostLabel = computed(() => {
  const host = props.server.host || "—";
  if (props.server.port) {
    return `${host}:${props.server.port}`;
  }
  return host;
});

const typeLabel = computed(() => {
  const type = props.server.type;
  if (!type) return "Type inconnu";
  if (typeof type === "string") return type;
  return type.name || "Type inconnu";
});

const hasHost = computed(() => Boolean(props.server.host));

const statusLabel = computed(() => (hasHost.value ? "Configuré" : "Incomplet"));
const statusColor = computed<"success" | "warning">(() =>
  hasHost.value ? "success" : "warning"
);
const statusBgClass = computed(() =>
  hasHost.value
    ? "bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400"
    : "bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400"
);

function goToServer() {
  const id = getIdFromIri(props.server["@id"]) || props.server.id;
  if (id) {
    navigateTo(`/servers/${id}`);
  } else {
    navigateTo("/servers");
  }
}
</script>
