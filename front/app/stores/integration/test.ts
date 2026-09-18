import { defineStore } from "pinia";
import type { ConnectionTestResult, Integration } from "~/types/integration";
import { getEntrypoint } from "~/utils/config";

interface State {
  isLoading: boolean;
  result?: ConnectionTestResult;
  error?: string;
}

export const useIntegrationTestStore = defineStore("integrationTest", {
  state: (): State => ({
    isLoading: false,
    result: undefined,
    error: undefined,
  }),

  actions: {
    async testExisting(id: number | string): Promise<ConnectionTestResult> {
      this.isLoading = true;
      this.error = undefined;
      this.result = undefined;

      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/integrations/${id}/test`;
        const res = await $fetch<ConnectionTestResult>(url, {
          method: "POST",
          headers: {
            Accept: "application/json",
          },
        });

        this.result = res;
        return res;
      } catch (err: any) {
        const msg = err?.data?.statusMessage || err?.data?.message || err?.message || "Erreur de connexion";
        const errorResult: ConnectionTestResult = {
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

    async testTransient(payload: {
      type: string;
      server?: string | number | null;
    }): Promise<ConnectionTestResult> {
      this.isLoading = true;
      this.error = undefined;
      this.result = undefined;

      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/integrations/test`;
        const res = await $fetch<ConnectionTestResult>(url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: payload,
        });

        this.result = res;
        return res;
      } catch (err: any) {
        const msg = err?.data?.statusMessage || err?.data?.message || err?.message || "Erreur de connexion";
        const errorResult: ConnectionTestResult = {
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
