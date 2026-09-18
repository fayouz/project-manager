import { defineStore } from "pinia";
import type { ProjectIntegration } from "~/types/projectintegration";
import type { SubmissionErrors } from "~/types/error";
import { FetchItemData, UpdateItemData } from "~/types/api";

interface State {
  updated?: ProjectIntegration;
  retrieved?: ProjectIntegration;
  isLoading: boolean;
  error?: string;
  hubUrl?: URL;
  violations?: SubmissionErrors;
}

export const useProjectIntegrationUpdateStore = defineStore(
  "projectintegrationUpdate",
  {
    state: (): State => ({
      updated: undefined,
      retrieved: undefined,
      isLoading: false,
      error: undefined,
      hubUrl: undefined,
      violations: undefined,
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

      setUpdateData({
        updated,
        isLoading,
        error,
        violations,
      }: UpdateItemData<ProjectIntegration>) {
        this.setUpdated(updated.value);
        this.setLoading(isLoading.value);
        this.setViolations(violations.value);

        if (error.value instanceof Error) {
          this.setError(error.value?.message);
        }
      },

      setRetrieved(retrieved?: ProjectIntegration) {
        this.retrieved = retrieved;
      },

      setUpdated(updated?: ProjectIntegration) {
        this.updated = updated;
      },

      setHubUrl(hubUrl?: URL) {
        this.hubUrl = hubUrl;
      },

      setLoading(isLoading: boolean) {
        this.isLoading = isLoading;
      },

      setError(error?: string) {
        this.error = error;
      },

      setViolations(violations?: SubmissionErrors) {
        this.violations = violations;
      },
    },
  }
);
