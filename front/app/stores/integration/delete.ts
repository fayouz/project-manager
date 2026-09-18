import { defineStore } from "pinia";
import type { Integration } from "~/types/integration";

interface State {
  deleted?: Integration;
  mercureDeleted?: Integration;
  isLoading: boolean;
  error?: string;
}

export const useIntegrationDeleteStore = defineStore("integrationDelete", {
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

    setDeleted(deleted: Integration) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Integration | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
