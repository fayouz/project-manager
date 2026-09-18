import { defineStore } from "pinia";
import type { Team } from "~/types/team";

interface State {
  deleted?: Team;
  mercureDeleted?: Team;
  isLoading: boolean;
  error?: string;
}

export const useTeamDeleteStore = defineStore("teamDelete", {
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

    setDeleted(deleted: Team) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Team | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
