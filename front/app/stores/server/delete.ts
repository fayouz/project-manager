import { defineStore } from "pinia";
import type { Server } from "~/types/server";

interface State {
  deleted?: Server;
  mercureDeleted?: Server;
  isLoading: boolean;
  error?: string;
}

export const useServerDeleteStore = defineStore("serverDelete", {
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

    setDeleted(deleted: Server) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Server | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
