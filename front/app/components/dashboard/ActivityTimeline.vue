<template>
  <div>
    <div v-if="isLoading" class="space-y-3">
      <USkeleton v-for="n in 4" :key="n" class="h-10 w-full" />
    </div>

    <div v-else-if="error" class="text-center py-6">
      <UIcon name="i-heroicons-exclamation-triangle" class="mx-auto size-6 text-amber-500" />
      <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ error }}</p>
    </div>

    <div v-else-if="activities.length === 0" class="text-center py-8">
      <UIcon name="i-heroicons-clock" class="mx-auto size-8 text-neutral-400" />
      <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aucune activité récente.</p>
    </div>

    <ul v-else class="space-y-3">
      <li
        v-for="activity in activities"
        :key="activity['@id'] || activity.id"
        class="flex items-start gap-3"
      >
        <div
          class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
          :class="getActionIconBg(activity.action)"
        >
          <UIcon :name="getEntityIcon(activity.entityType)" class="w-4 h-4" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-neutral-900 dark:text-neutral-100">
            <span class="font-medium">{{ getActorLabel(activity) }}</span>
            {{ getActionLabel(activity.action) }}
            <span class="font-medium">{{ activity.entityLabel || activity.entityType }}</span>
          </p>
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            {{ formatRelativeTime(activity.createdAt) }}
          </p>
        </div>
        <UBadge :color="getActionColor(activity.action)" variant="subtle" size="xs">
          {{ activity.entityType }}
        </UBadge>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import type { ActivityLog } from "~/types/activitylog";

defineProps<{
  activities: ActivityLog[];
  isLoading?: boolean;
  error?: string;
}>();

function getActorLabel(activity: ActivityLog): string {
  const actor = activity.actor as any;
  if (!actor) return "Quelqu'un";
  if (typeof actor === "string") return actor;
  return actor.displayName || actor.username || "Quelqu'un";
}

function getActionLabel(action?: string): string {
  switch (action) {
    case "created":
      return "a créé";
    case "updated":
      return "a modifié";
    case "deleted":
      return "a supprimé";
    default:
      return "a modifié";
  }
}

function getActionColor(action?: string): "success" | "warning" | "error" | "neutral" {
  switch (action) {
    case "created":
      return "success";
    case "updated":
      return "warning";
    case "deleted":
      return "error";
    default:
      return "neutral";
  }
}

function getActionIconBg(action?: string): string {
  switch (action) {
    case "created":
      return "bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400";
    case "updated":
      return "bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400";
    case "deleted":
      return "bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400";
    default:
      return "bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400";
  }
}

function getEntityIcon(entityType?: string): string {
  switch (entityType) {
    case "project":
      return "i-heroicons-folder";
    case "organisation":
      return "i-heroicons-building-office-2";
    case "integration":
      return "i-heroicons-puzzle-piece";
    case "server":
      return "i-heroicons-server";
    case "user":
      return "i-heroicons-user";
    default:
      return "i-heroicons-sparkles";
  }
}

function formatRelativeTime(dateStr?: string): string {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  const diffMs = Date.now() - date.getTime();
  const diffMin = Math.floor(diffMs / 60000);

  if (diffMin < 1) return "à l'instant";
  if (diffMin < 60) return `il y a ${diffMin} min`;
  const diffHour = Math.floor(diffMin / 60);
  if (diffHour < 24) return `il y a ${diffHour} h`;
  const diffDay = Math.floor(diffHour / 24);
  return `il y a ${diffDay} j`;
}
</script>
