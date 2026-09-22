import { defineStore } from "pinia";
import type { Proxy } from "~/types/proxy";

interface State {
  deleted?: Proxy;
  mercureDeleted?: Proxy;
  isLoading: boolean;
  error?: string;
}

export const useProxyDeleteStore = defineStore("proxyDelete", {
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

    setDeleted(deleted: Proxy) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: Proxy | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
