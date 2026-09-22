import { defineStore } from "pinia";
import type { Staging } from "~/types/staging";

interface State {
  deleted?: Staging;
  mercureDeleted?: Staging;
  isLoading: boolean;
  error?: string;
}

export const useStagingDeleteStore = defineStore("stagingDelete", {
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

    setDeleted(deleted: Staging) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Staging | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
