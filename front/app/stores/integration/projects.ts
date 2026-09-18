import { defineStore } from "pinia";
import { getEntrypoint } from "~/utils/config";

export interface IntegrationProjectItem {
  id: string;
  name: string;
  raw_name?: string;
}

interface State {
  isLoading: boolean;
  projects: IntegrationProjectItem[];
  error?: string;
}

export const useIntegrationProjectsStore = defineStore("integrationProjects", {
  state: (): State => ({
    isLoading: false,
    projects: [],
    error: undefined,
  }),

  actions: {
    async fetchProjects(integrationId: number | string): Promise<IntegrationProjectItem[]> {
      this.isLoading = true;
      this.error = undefined;

      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/integrations/${integrationId}/projects`;
        const res = await $fetch<{ success: boolean; projects: IntegrationProjectItem[]; count: number }>(url, {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        });

        this.projects = res.projects || [];
        return this.projects;
      } catch (err: any) {
        const msg = err?.data?.message || err?.message || "Impossible de charger la liste des projets.";
        this.error = msg;
        this.projects = [];
        return [];
      } finally {
        this.isLoading = false;
      }
    },

    async fetchJobs(integrationId: number | string, folder: string): Promise<any[]> {
      try {
        const entrypoint = getEntrypoint();
        const url = `${entrypoint}/integrations/${integrationId}/projects?folder=${encodeURIComponent(folder)}`;
        const res = await $fetch<{ success: boolean; jobs: any[]; count: number }>(url, {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        });
        return res.jobs || [];
      } catch {
        return [];
      }
    },

    clear() {
      this.isLoading = false;
      this.projects = [];
      this.error = undefined;
    },
  },
});
