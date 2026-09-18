import { defineStore } from "pinia";
import type { IntegrationLiveDataResponse } from "~/types/liveData";
import { getEntrypoint } from "~/utils/config";

interface State {
  loadingMap: Record<string, boolean>;
  dataMap: Record<string, any>;
  errorMap: Record<string, string | undefined>;
}

export const useProjectIntegrationLiveDataStore = defineStore("projectIntegrationLiveData", {
  state: (): State => ({
    loadingMap: {},
    dataMap: {},
    errorMap: {},
  }),

  actions: {
    async fetchLiveData<T = any>(id: number | string, force = false): Promise<T | null> {
      const key = String(id);
      if (!force && this.dataMap[key]) {
        return this.dataMap[key] as T;
      }

      this.loadingMap[key] = true;
      this.errorMap[key] = undefined;

      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/project_integrations/${id}/live-data`;
        const res = await $fetch<IntegrationLiveDataResponse<T>>(url, {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        });

        if (res.success && res.data) {
          this.dataMap[key] = res.data;
          return res.data;
        }

        const msg = res.message || "Impossible de charger les données du service.";
        this.errorMap[key] = msg;
        return null;
      } catch (err: any) {
        const msg =
          err?.data?.message ||
          err?.message ||
          "Erreur lors de la récupération des données en direct.";
        this.errorMap[key] = msg;
        return null;
      } finally {
        this.loadingMap[key] = false;
      }
    },

    getData<T = any>(id: number | string): T | undefined {
      return this.dataMap[String(id)] as T | undefined;
    },

    getLiveData<T = any>(id: number | string): T | undefined {
      return this.getData<T>(id);
    },

    isLoading(id: number | string): boolean {
      return !!this.loadingMap[String(id)];
    },

    getError(id: number | string): string | undefined {
      return this.errorMap[String(id)];
    },

    clear(id?: number | string) {
      if (id !== undefined) {
        const key = String(id);
        delete this.dataMap[key];
        delete this.loadingMap[key];
        delete this.errorMap[key];
      } else {
        this.dataMap = {};
        this.loadingMap = {};
        this.errorMap = {};
      }
    },
  },
});
