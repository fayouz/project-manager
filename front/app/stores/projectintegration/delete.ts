import { defineStore } from "pinia";
import type { ProjectIntegration } from "~/types/projectintegration";

interface State {
  deleted?: ProjectIntegration;
  mercureDeleted?: ProjectIntegration;
  isLoading: boolean;
  error?: string;
}

export const useProjectIntegrationDeleteStore = defineStore(
  "projectintegrationDelete",
  {
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

      setDeleted(deleted: ProjectIntegration) {
        this.deleted = deleted;
      },

      setMercureDeleted(mercureDeleted: ProjectIntegration | undefined) {
        this.mercureDeleted = mercureDeleted;
      },

      setError(error: string) {
        this.error = error;
      },
    },
  }
);
