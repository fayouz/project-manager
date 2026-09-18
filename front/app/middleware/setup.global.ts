import { getEntrypoint } from "~/utils/config";

export default defineNuxtRouteMiddleware(async (to) => {
  const isInstalled = useState<boolean | null>("app_is_installed", () => null);

  // Si on a déjà validé que l'application est installée dans la session
  if (isInstalled.value === true) {
    if (to.path === "/setup") {
      return navigateTo("/login");
    }
    return;
  }

  try {
    const reqHeaders = useRequestHeaders(["x-test-env"]);
    const res = await $fetch<{ installed: boolean }>("install-status", {
      baseURL: getEntrypoint(),
      headers: reqHeaders,
    });

    isInstalled.value = res.installed;

    if (res.installed === false && to.path !== "/setup") {
      return navigateTo("/setup");
    }

    if (res.installed === true && to.path === "/setup") {
      return navigateTo("/login");
    }
  } catch (e) {
    console.error("Erreur lors de la vérification de l'état de l'installation", e);
  }
});
