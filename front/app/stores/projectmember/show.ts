import { defineStore } from "pinia";
import type { ProjectMember } from "~/types/projectmember";
import { FetchItemData } from "~/types/api";
interface State {
  retrieved?: ProjectMember;
  isLoading: boolean;
  error?: string;
  hubUrl?: URL;
}

export const useProjectMemberShowStore = defineStore("projectmemberShow", {
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
    }: FetchItemData<ProjectMember>) {
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

    setRetrieved(retrieved?: ProjectMember) {
      this.retrieved = retrieved;
    },

    setHubUrl(hubUrl?: URL) {
      this.hubUrl = hubUrl;
    },

    setError(error?: string) {
      this.error = error;
    },
  },
});
