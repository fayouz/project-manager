import { defineStore } from "pinia";
import type { Project } from "~/types/project";

interface State {
  deleted?: Project;
  mercureDeleted?: Project;
  isLoading: boolean;
  error?: string;
}

export const useProjectDeleteStore = defineStore("projectDelete", {
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

    setDeleted(deleted: Project) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Project | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
