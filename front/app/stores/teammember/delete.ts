import { defineStore } from "pinia";
import type { TeamMember } from "~/types/teammember";

interface State {
  deleted?: TeamMember;
  mercureDeleted?: TeamMember;
  isLoading: boolean;
  error?: string;
}

export const useTeamMemberDeleteStore = defineStore("teammemberDelete", {
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

    setDeleted(deleted: TeamMember) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: TeamMember | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
