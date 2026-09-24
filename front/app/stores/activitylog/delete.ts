import { defineStore } from "pinia";
import type { ActivityLog } from "~/types/activitylog";

interface State {
  deleted?: ActivityLog;
  mercureDeleted?: ActivityLog;
  isLoading: boolean;
  error?: string;
}

export const useActivityLogDeleteStore = defineStore("activitylogDelete", {
  state: (): State => ({
    deleted: undefined,
    mercureDeleted: undefined,
    isLoading: false,
    error: undefined,
  }),

  actions: {
    setLoading(isLoading: boolean) {
      this.isLoading = isLoading;
    },

    setDeleted(deleted: ActivityLog) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: ActivityLog | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
