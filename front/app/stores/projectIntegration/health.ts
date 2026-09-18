import { defineStore } from "pinia";
import type { ConnectionTestResult } from "~/types/integration";
import { getEntrypoint } from "~/utils/config";

export interface ProjectHealthResult extends ConnectionTestResult {
  targetDisplay?: string;
  parameters?: Record<string, any>;
}

interface State {
  isLoading: boolean;
  result?: ProjectHealthResult;
  error?: string;
}

export const useProjectIntegrationHealthStore = defineStore("projectIntegrationHealth", {
  state: (): State => ({
    isLoading: false,
    result: undefined,
    error: undefined,
  }),

  actions: {
    async checkHealth(id: number | string): Promise<ProjectHealthResult> {
      this.isLoading = true;
      this.error = undefined;
      this.result = undefined;

      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/project_integrations/${id}/health`;
        const res = await $fetch<ProjectHealthResult>(url, {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        });

        this.result = res;
        return res;
      } catch (err: any) {
        const msg =
          err?.data?.statusMessage ||
          err?.data?.message ||
          err?.message ||
          "Erreur lors de la vérification de santé de l'intégration";
        const errorResult: ProjectHealthResult = {
          success: false,
          status: "error",
          statusMessage: msg,
        };
        this.result = errorResult;
        this.error = msg;
        return errorResult;
      } finally {
        this.isLoading = false;
      }
    },

    reset() {
      this.isLoading = false;
      this.result = undefined;
      this.error = undefined;
    },
  },
});
