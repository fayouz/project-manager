import { defineStore } from "pinia";
import type { Organisation } from "~/types/organisation";

interface State {
  deleted?: Organisation;
  mercureDeleted?: Organisation;
  isLoading: boolean;
  error?: string;
}

export const useOrganisationDeleteStore = defineStore("organisationDelete", {
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

    setDeleted(deleted: Organisation) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Organisation | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
