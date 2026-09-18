import { defineStore } from "pinia";
import type { ProjectIntegration } from "~/types/projectintegration";
import { FetchItemData } from "~/types/api";
interface State {
  retrieved?: ProjectIntegration;
  isLoading: boolean;
  error?: string;
  hubUrl?: URL;
}

export const useProjectIntegrationShowStore = defineStore(
  "projectintegrationShow",
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
      }: FetchItemData<ProjectIntegration>) {
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

      setRetrieved(retrieved?: ProjectIntegration) {
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
