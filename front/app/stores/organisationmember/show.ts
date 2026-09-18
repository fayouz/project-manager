import { defineStore } from "pinia";
import type { OrganisationMember } from "~/types/organisationmember";
import { FetchItemData } from "~/types/api";
interface State {
  retrieved?: OrganisationMember;
  isLoading: boolean;
  error?: string;
  hubUrl?: URL;
}

export const useOrganisationMemberShowStore = defineStore(
  "organisationmemberShow",
  {
    state: (): State => ({
      retrieved: undefined,
      isLoading: false,
      error: "",
      hubUrl: undefined,
    }),

    actions: {
      setData({
        retrieved,
        isLoading,
        error,
        hubUrl,
      }: FetchItemData<OrganisationMember>) {
        this.setRetrieved(retrieved.value);
        this.setLoading(isLoading.value);
        this.setHubUrl(hubUrl.value);

        if (error.value instanceof Error) {
          this.setError(error.value?.message);
        }
      },

      setLoading(isLoading: boolean) {
        this.isLoading = isLoading;
      },

      setRetrieved(retrieved?: OrganisationMember) {
        this.retrieved = retrieved;
      },

      setHubUrl(hubUrl?: URL) {
        this.hubUrl = hubUrl;
      },

      setError(error?: string) {
        this.error = error;
      },
    },
  }
);
