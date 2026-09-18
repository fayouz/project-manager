import { defineStore } from "pinia";
import type { ProjectMember } from "~/types/projectmember";

interface State {
  deleted?: ProjectMember;
  mercureDeleted?: ProjectMember;
  isLoading: boolean;
  error?: string;
}

export const useProjectMemberDeleteStore = defineStore("projectmemberDelete", {
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

    setDeleted(deleted: ProjectMember) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: ProjectMember | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
