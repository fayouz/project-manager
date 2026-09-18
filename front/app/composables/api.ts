import type { PagedCollection } from "~/types/collection";
import type { FetchAllData, FetchItemData } from "~/types/api";
import { type Ref, ref } from "vue";
import type { View } from "~/types/view";
import type { UseFetchOptions } from "#app";
import type { SubmissionErrors } from "~/types/error";
import type { Item } from "~/types/item";
import { getEntrypoint } from "~/utils/config";
import { extractHubURL } from "~/utils/mercure";
import { SubmissionError } from "~/utils/error";

const MIME_TYPE = "application/ld+json";

async function useApi<T>(path: string, options: UseFetchOptions<T>) {
  const token = useCookie<string | null>("jwt_token").value;
  const authHeaders: Record<string, string> = token
    ? { Authorization: `Bearer ${token}` }
    : {};

  let cleanPath = path;
  if (!cleanPath.startsWith("http://") && !cleanPath.startsWith("https://")) {
    if (cleanPath.startsWith("/api/")) {
      cleanPath = cleanPath.substring(5);
    } else if (cleanPath === "/api") {
      cleanPath = "";
    } else if (cleanPath.startsWith("/")) {
      cleanPath = cleanPath.substring(1);
    }
  }

  const response = await useFetch(cleanPath, {
    baseURL: getEntrypoint(),
    mode: "cors",
    headers: {
      Accept: MIME_TYPE,
      ...authHeaders,
      ...options.headers,
    },
    onResponseError({ response }) {
      if (response.status === 401 && typeof window !== "undefined") {
        const tokenCookie = useCookie<string | null>("jwt_token");
        tokenCookie.value = null;
        navigateTo("/login");
      }

      const data = response._data;
      const error =
        data?.["hydra:description"] ||
        data?.description ||
        data?.detail ||
        response.statusText;

      throw new Error(error);
    },
    ...options,
  });

  return response;
}

export async function useFetchList<T>(
  resource: string
): Promise<FetchAllData<T>> {
  const route = useRoute();

  const items: Ref<T[]> = ref([]);
  const view: Ref<View | undefined> = ref(undefined);
  const hubUrl: Ref<URL | undefined> = ref(undefined);

  const page = ref(route.params.page);

  const { data, pending, error } = await useApi<any>(resource, {
    params: { page },
    onResponse({ response }) {
      hubUrl.value = extractHubURL(response);
    },
  });

  const value = data.value;
  items.value = value?.["hydra:member"] || value?.member || [];
  view.value = value?.["hydra:view"] || value?.view;

  return {
    items,
    view,
    isLoading: pending,
    error,
    hubUrl,
  };
}

export async function useFetchItem<T>(path: string): Promise<FetchItemData<T>> {
  const retrieved: Ref<T | undefined> = ref(undefined);
  const hubUrl: Ref<URL | undefined> = ref(undefined);

  const { data, pending, error } = await useApi<T>(path, {
    onResponse({ response }) {
      retrieved.value = response._data;
      hubUrl.value = extractHubURL(response);
    },
  });

  retrieved.value = data.value as T;

  return {
    retrieved,
    isLoading: pending,
    error,
    hubUrl,
  };
}

export async function useCreateItem<T>(resource: string, payload: Item) {
  const created: Ref<T | undefined> = ref(undefined);
  const violations: Ref<SubmissionErrors | undefined> = ref(undefined);

  const { data, pending, error } = await useApi(resource, {
    method: "POST",
    body: payload,
    headers: {
      Accept: MIME_TYPE,
      "Content-Type": MIME_TYPE,
    },
    onResponseError({ response }) {
      const data = response._data;
      const errorMsg =
        data?.["hydra:description"] ||
        data?.description ||
        data?.detail ||
        response.statusText;

      const violationItems = data?.violations || data?.["hydra:violations"];
      if (!violationItems) throw new Error(errorMsg);

      const errors: SubmissionErrors = { _error: errorMsg };
      violationItems.forEach(
        (violation: { propertyPath: string; message: string }) => {
          errors[violation.propertyPath] = violation.message;
        }
      );

      violations.value = errors;
      throw new SubmissionError(errors);
    },
  });

  created.value = data.value as T;

  return {
    created,
    isLoading: pending,
    error,
    violations,
  };
}

export async function useUpdateItem<T>(item: Item, payload: Item) {
  const updated: Ref<T | undefined> = ref(undefined);
  const violations: Ref<SubmissionErrors | undefined> = ref(undefined);

  const { data, pending, error } = await useApi(item["@id"] ?? "", {
    method: "PUT",
    body: payload,
    headers: {
      Accept: MIME_TYPE,
      "Content-Type": MIME_TYPE,
    },
    onResponseError({ response }) {
      const data = response._data;
      const errorMsg =
        data?.["hydra:description"] ||
        data?.description ||
        data?.detail ||
        response.statusText;

      const violationItems = data?.violations || data?.["hydra:violations"];
      if (!violationItems) throw new Error(errorMsg);

      const errors: SubmissionErrors = { _error: errorMsg };
      violationItems.forEach(
        (violation: { propertyPath: string; message: string }) => {
          errors[violation.propertyPath] = violation.message;
        }
      );

      violations.value = errors;
      throw new SubmissionError(errors);
    },
  });

  updated.value = data.value as T;

  return {
    updated,
    isLoading: pending,
    error,
    violations,
  };
}

export async function useDeleteItem(item: Item) {
  const error: Ref<string | undefined> = ref(undefined);

  if (!item?.["@id"]) {
    error.value = "No item found. Please reload";
    return {
      error,
    };
  }

  const { pending } = await useApi(item["@id"] ?? "", { method: "DELETE" });

  return {
    isLoading: pending,
    error,
  };
}
