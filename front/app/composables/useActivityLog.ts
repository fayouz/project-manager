import { ref } from "vue";
import { useFetchList } from "~/composables/api";
import type { ActivityLog } from "~/types/activitylog";

export function useActivityLog() {
  const activities = ref<ActivityLog[]>([]);
  const isLoading = ref(true);
  const error = ref<string | undefined>(undefined);

  async function fetchRecent(limit = 15) {
    isLoading.value = true;
    error.value = undefined;

    try {
      const { items } = await useFetchList<ActivityLog>("activity_logs");
      activities.value = (items.value || []).slice(0, limit);
    } catch (err: any) {
      error.value = err?.message || "Impossible de charger l'historique d'activité.";
    } finally {
      isLoading.value = false;
    }
  }

  return {
    activities,
    isLoading,
    error,
    fetchRecent,
  };
}
