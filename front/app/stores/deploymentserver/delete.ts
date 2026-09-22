import { defineStore } from "pinia";
import type { DeploymentServer } from "~/types/deploymentserver";

interface State {
  deleted?: DeploymentServer;
  mercureDeleted?: DeploymentServer;
  isLoading: boolean;
  error?: string;
}

export const useDeploymentServerDeleteStore = defineStore(
  "deploymentserverDelete",
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

      setDeleted(deleted: DeploymentServer) {
        this.deleted = deleted;
      },

      setMercureDeleted(mercureDeleted: DeploymentServer | undefined) {
        this.mercureDeleted = mercureDeleted;
      },

      setError(error: string) {
        this.error = error;
      },
    },
  }
);
