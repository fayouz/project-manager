import { defineStore } from "pinia";
import type { DeploymentServer } from "~/types/deploymentserver";
import type { SubmissionErrors } from "~/types/error";
import { FetchItemData, UpdateItemData } from "~/types/api";

interface State {
  updated?: DeploymentServer;
  retrieved?: DeploymentServer;
  isLoading: boolean;
  error?: string;
  hubUrl?: URL;
  violations?: SubmissionErrors;
}

export const useDeploymentServerUpdateStore = defineStore(
  "deploymentserverUpdate",
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
      }: FetchItemData<DeploymentServer>) {
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
      }: UpdateItemData<DeploymentServer>) {
        this.setUpdated(updated.value);
        this.setLoading(isLoading.value);
        this.setViolations(violations.value);

        if (error.value instanceof Error) {
          this.setError(error.value?.message);
        }
      },

      setRetrieved(retrieved?: DeploymentServer) {
        this.retrieved = retrieved;
      },

      setUpdated(updated?: DeploymentServer) {
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
