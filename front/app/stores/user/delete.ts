import { defineStore } from "pinia";
import type { User } from "~/types/user";

interface State {
  deleted?: User;
  mercureDeleted?: User;
  isLoading: boolean;
  error?: string;
}

export const useUserDeleteStore = defineStore("userDelete", {
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

    setDeleted(deleted: User) {
      this.deleted = deleted;
    },

    setMercureDeleted(mercureDeleted: User | undefined) {
      this.mercureDeleted = mercureDeleted;
    },

    setError(error: string) {
      this.error = error;
    },
  },
});
