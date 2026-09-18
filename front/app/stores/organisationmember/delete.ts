import { defineStore } from "pinia";
import type { OrganisationMember } from "~/types/organisationmember";

interface State {
  deleted?: OrganisationMember;
  mercureDeleted?: OrganisationMember;
  isLoading: boolean;
  error?: string;
}

export const useOrganisationMemberDeleteStore = defineStore(
  "organisationmemberDelete",
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

      setDeleted(deleted: OrganisationMember) {
        this.deleted = deleted;
      },

      setMercureDeleted(mercureDeleted: OrganisationMember | undefined) {
        this.mercureDeleted = mercureDeleted;
      },

      setError(error: string) {
        this.error = error;
      },
    },
  }
);
